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

            $discValue = $this->availableDiscount($moneyParser, $subtotal, $quantity, $unitPrice, $product['id'], $product['categoryId'], $request );
            array_push($discount_array, $discValue);

        }
        $discount = Money::max(...$discount_array);

        $total = $subtotal->subtract($discount);
      //  $strategy = 'none';
        $strategy = 'take-3-pay-2';

        return new JsonResponse(
            [
                'message' => 'Success.',
                'data' => [
                    'subtotal' => $moneyFormatter->format($subtotal),
                    'discount' => $moneyFormatter->format($discount),
                    'total' => $moneyFormatter->format($total),
                   // 'strategy' => $strategy,
                ],
            ]
        );
    }

    /* check if is discountable */

    private function availableDiscount(MoneyParser $moneyParser, Money $subtotal, ?int $quantity = null, ?Money $unitPrice = null, ?string $product_id, ?string $product_category,  ?CartDiscountRequest $request) : Money {

        $userEmail = $request->get('userEmail');
        $requested = Request::create('/api/v1/user/'.$userEmail, 'GET');
        $response = app()->handle($requested);
        $user = $response->getOriginalContent();
        $code = $response->getStatusCode();
        //dd($code);
       // dd($this->isUserExist($code));

       // $codeType = gettype($code);
        $integerValueOfAmount = Money::BRL(300000);
        $discount = Money::BRL(0);

        if($subtotal->greaterThanOrEqual($integerValueOfAmount) ) {
            if(!$this->isMultipleOf3($quantity) ) {
                $discount = $this->getFiftyPercentage($subtotal);
            }else {
                if($this->isPromotionalProductForTakeThreePayTwo($product_id)) {
                    $discount =  $this->getDiscountWhenStrategieIsTake3Pay2($unitPrice, $quantity);
                }else {
                    $discount = $this->getFiftyPercentage($subtotal);

                }
            }

        }elseif ($unitPrice && $quantity && $this->isMultipleOf3($quantity)) {
            if($this->isPromotionalProductForTakeThreePayTwo($product_id)) {
                $discount =  $this->getDiscountWhenStrategieIsTake3Pay2($unitPrice, $quantity);
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
                   $discount = $this->getFourtyPercent($tableauDesPrix[0]);

               }
               else{
                   $discount = $this->getFourtyPercent(Money::min(...$tableauDesPrix));
               }
           }

        }elseif ($this->isUserExist($code) ) {
            if($this->isUserCollaborator($user)) {
                $discount = $this->getTwentyPercent($subtotal);
            }

        }elseif (!$this->isUserExist($code) && $code === 404) {
          $mininumAmountForNewCustomer = Money::BRL(5000);
          $fixedDiscount = Money::BRL(2500);
          if($subtotal->greaterThan($mininumAmountForNewCustomer)) {
              $discount = $discount->add($fixedDiscount);
          }

        }

        return $discount;

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

    private function getStrategy(?int $quantity = null, ?Money $subtotal) : string {
        $valueToCompareForAbove3000 = Money::BRL(300000);
        if($quantity && $this->isMultipleOf3($quantity) ) {
            return 'take-3-pay-2';
        }
        elseif ($subtotal->greaterThanOrEqual($valueToCompareForAbove3000)) {
            return 'above-3000';
        }else {
            return 'none';
        }

    }

    private function getStra(Money $subtotal, ?int $quantity = null, ?Money $unitPrice) : Money {


        $integerValueOfAmount = Money::BRL(300000);
        $discount = Money::BRL(0);

        if($subtotal->greaterThanOrEqual($integerValueOfAmount) ) {
            if(!$this->isMultipleOf3($quantity) ) {
                $dis = $subtotal->multiply(15);
                $discountForAbove3000 = $dis->divide(100);

                $discount =  $discount->add($discountForAbove3000);


            }else {
                $discount =  $this->getDiscountWhenStrategieIsTake3Pay2($unitPrice, $quantity);

            }

        }elseif ($unitPrice && $quantity && $this->isMultipleOf3($quantity)) {
            $discount =  $this->getDiscountWhenStrategieIsTake3Pay2($unitPrice, $quantity);
        }

        return $discount;

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

    private function getFourtyPercent(Money $subtotal) : Money {
        $dis = $subtotal->multiply(40);

        return $dis->divide(100);

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







}
