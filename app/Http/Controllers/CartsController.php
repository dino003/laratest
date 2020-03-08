<?php
namespace App\Http\Controllers;

use App\Http\Requests\CartDiscountRequest;
use Illuminate\Http\JsonResponse;
use Money\Money;
use Money\MoneyFormatter;
use Money\MoneyParser;
use Symfony\Component\HttpFoundation\Request;


class CartsController extends Controller
{
    public function calculateDiscount(
        CartDiscountRequest $request,
        MoneyParser $moneyParser,
        MoneyFormatter $moneyFormatter
    ): JsonResponse {
        // Your logic goes here, use the code below just as a guidance.
        // You can do whatever you want with this code, even delete it.
        // Think about responsibilities, testing and code clarity.
      //  dd($this->getPromotionalTakeThreePayTwo());


        $subtotal = Money::BRL(0);

        $discount_array = [];
        foreach ($request->get('products') as $product) {
            $quantity = $product['quantity'];
            $productUnitPrice = $product['unitPrice'];

            $unitPrice = $moneyParser->parse($productUnitPrice, 'BRL');

            $amount = $unitPrice->multiply($quantity);

            $subtotal = $subtotal->add($amount);

            $discValue = $this->availableDiscount($moneyParser, $subtotal, $quantity, $unitPrice, $product['id'], $product['categoryId'], $request, $moneyFormatter );
           // array_push($discount_array, $discValue['discount']);
            $discountFinal = $this->checkBiggestDiscountBeforePushInArray($discValue['discount'], $discValue['strategy'], $discount_array);

        }
       //  dd($discountFinal);

        // dd($discountFinal);
        //  $discount = Money::max(...$discount_array);
        $discount = $discountFinal['discount'];

        $strategy = $discountFinal['strategy'];
       // dd($discValue);

        $total = $subtotal->subtract($discount);

        return new JsonResponse(
            [
                'message' => 'Success.',
                'data' => [
                    'subtotal' => $moneyFormatter->format($subtotal),
                    'discount' => $moneyFormatter->format($discount),
                    'total' => $moneyFormatter->format($total),
                    'strategy' => $strategy,
                ],
            ]
        );
    }

    /* check if is discountable */

    private function availableDiscount(MoneyParser $moneyParser, Money $subtotal, ?int $quantity = null, ?Money $unitPrice = null, ?string $product_id, ?string $product_category,  ?CartDiscountRequest $request, MoneyFormatter $moneyFormatter) : array {

        $userEmail = $request->get('userEmail');
        $requested = Request::create('/api/v1/user/'.$userEmail, 'GET');
        $response = app()->handle($requested);
        $user = $response->getOriginalContent();
        $code = $response->getStatusCode();

        $integerValueOfAmount = Money::BRL(300000);
        $discount = Money::BRL(0);
        $strategy = 'none';
        $result = array("discount" => $discount, "strategy" => $strategy);

        if($subtotal->greaterThanOrEqual($integerValueOfAmount) ) {
                foreach ($request->get('products') as $key => $product) {
                    if(!$this->isMultipleOf3($product['quantity']) ) {
                        // dd($unitPrice);
                        $discount = $this->getFiftyPercentage($subtotal);
                        $result = array("discount" => $discount, "strategy" => 'above-3000');
                        // dd($result);
                    }else {
                        if($this->isPromotionalProductForTakeThreePayTwo($product['id'])) {
                           // dd($product['quantity']);
                            $discount1 =  $this->getDiscountWhenStrategieIsTake3Pay2($moneyParser->parse($product['unitPrice'], 'BRL'), $product['quantity']);
                          // dd($discount1);
                            $disc2 = $this->getFiftyPercentage($subtotal);
                           // dd($disc2);
                            $discount = Money::max($discount1, $disc2);
                           // dd($discount);
                            $result = array("discount" => $discount, "strategy" => 'take-3-pay-2');
                           // dd($result);
                        }else {
                            $discount = $this->getFiftyPercentage($subtotal);
                            $result = array("discount" => $discount, "strategy" => 'above-3000');

                        }
                    }
                }
        }elseif ($unitPrice && $quantity && $this->isMultipleOf3($quantity)) {
            if($this->isPromotionalProductForTakeThreePayTwo($product_id)) {
                $discount =  $this->getDiscountWhenStrategieIsTake3Pay2($unitPrice, $quantity);
                $result = array("discount" => $discount, "strategy" => 'take-3-pay-2');

            }
        }elseif ($this->isPromotionalCategory($product_category)) {
            $producCate = "";
            $nbrProductSameCate = 1;
            $tableauDesPrix = [];
            $tableauDesPrixStrings = [];
            foreach ($request->get('products') as $product) {
                array_push($tableauDesPrix, $moneyParser->parse($product['unitPrice'], 'BRL'));
                array_push($tableauDesPrixStrings, $product['unitPrice']);

                if($product_category == $producCate) {
                    $nbrProductSameCate  += 1;

                }else{
                  //  array_push($tableauDesPrix, $product['unitPrice']);

                    $producCate  = $product['categoryId'];

                }
            }


            if($nbrProductSameCate > 1) {
               if(count(array_unique($tableauDesPrixStrings)) == 1) {
                   $discount = $this->getFourtyPercent($tableauDesPrix[0], $moneyFormatter, $moneyParser);
                   $result = array("discount" => $discount, "strategy" => 'same-category');


               }
               else{
                   $discount = $this->getFourtyPercent(Money::min(...$tableauDesPrix), $moneyFormatter, $moneyParser);
                   $result = array("discount" => $discount, "strategy" => 'same-category');

               }
           }

        }elseif ($this->isUserExist($code) ) {
            if($this->isUserCollaborator($user)) {
                $discount = $this->getTwentyPercent($subtotal);
                $result = array("discount" => $discount, "strategy" => 'employee');

            }

        }elseif (!$this->isUserExist($code) && $code === 404) {
          $mininumAmountForNewCustomer = Money::BRL(5000);
          $fixedDiscount = Money::BRL(2500);
          if($subtotal->greaterThan($mininumAmountForNewCustomer)) {
              $discount = $discount->add($fixedDiscount);
              $result = array("discount" => $discount, "strategy" => 'new-user');

          }

        }
      //  dd($result);
        //return $discount;
       // dd($result);
        return $result;

    }

    private function getDiscountWhenStrategieIsTake3Pay2(Money $unitPrice, int $quantity) {
       return $discountForTake3Pay2 = $unitPrice->multiply($this->quantityToDiscount($quantity));
    }

    /* check if is multiple of 3 */

    private function isMultipleOf3( $quantity)  {
        return $quantity % 3 === 0;
    }

    private function quantityToBy(int $quantity) : int {
        if($this->isMultipleOf3($quantity) ) return $quantity - intval($quantity / 3);

         return $quantity;

     /*    else if($quantity > 3) {

        } */


    }

    private function quantityToDiscount(int $quantity) : int {
        return abs($quantity - $this->quantityToBy(($quantity)));
    }



    private function checkBiggestDiscountBeforePushInArray(Money $discount , string $strategy, array $disc_arr) : array {
        if(empty($disc_arr)) {
            $disc_arr = array("discount" => $discount, "strategy" => $strategy);
        }else {
            if($discount->greaterThan($disc_arr['discount'])) {
                $disc_arr = array("discount" => $discount, "strategy" => $strategy);
            }
        }

        return $disc_arr;
    }



    private function isPromotionalProductForTakeThreePayTwo(string  $product_id) : bool
    {
        return in_array($product_id, config('api.promotional.products'));
    }

    private function isPromotionalCategory(string $product_category) : bool
    {
        return in_array($product_category, config('api.promotional.categories'));

    }

    private function getFiftyPercentage(Money $subtotal) : Money {
        $dis = $subtotal->multiply(15);
        return $dis->divide(100);

    }

    private function getFourtyPercent(Money $subtotal, MoneyFormatter $moneyFormatter, MoneyParser $moneyParser) : Money {
       // $dis = $subtotal->multiply(40);

       // return $dis->divide(100);

        $unitPrice = $moneyFormatter->format($subtotal);

        $dis = ((float) $unitPrice * 40) / 100;

        $dis = number_format(floor($dis * 100) / 100, 2, '.', ',');

        return $moneyParser->parse($dis, 'BRL');

    }

    private function getTwentyPercent(Money $subtotal) : Money {
        $dis = $subtotal->multiply(20);

        return $dis->divide(100);

    }

    private function isUserExist(int $code) : bool
    {
        return $code === 200 ?: false;
    }

    private function isUserCollaborator(array $userData) : bool
    {
        return $userData['data']['isEmployee'] ?: false;
    }

    private function discountable(CartDiscountRequest $request, Money $subtotal) {
        $userEmail = $request->get('userEmail');
        $requested = Request::create('/api/v1/user/'.$userEmail, 'GET');
        $response = app()->handle($requested);
        $user = $response->getOriginalContent();
        $code = $response->getStatusCode();

        $integerValueOfAmount = Money::BRL(300000);
        $discount = Money::BRL(0);
        $strategy = 'none';

        $result = array("discount" => $discount, "strategy" => $strategy );

        if($this->isUserExist($code)) { // when user exist
            if($this->isUserCollaborator($user)) { // user is collaborator
                $all_discount_available_for_this_user = array();
                $discountForEmployeeCase = $this->getTwentyPercent($subtotal);
                $result = array("discount" => $discount, "strategy" => 'employee');

            }else { // user is not collaborator

            }


        }else { //when user does not exist
            //user is new customer
            $fixedDiscountForNewUser = Money::BRL(2500);
            $minimumSubtotalToGetdiscount = Money::BRL(5000);
            if($subtotal->greaterThan($minimumSubtotalToGetdiscount)) {
                $discount = $discount->add($fixedDiscountForNewUser);
                $result = array("discount" => $discount, "strategy" => 'new-user');
            }else {
                $result = array("discount" => $discount, "strategy" => $strategy );

            }


        }

        return $result;

    }







}
