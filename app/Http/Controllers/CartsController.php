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

        foreach ($request->get('products') as $product) {
            $quantity = $product['quantity'];
            $productUnitPrice = $product['unitPrice'];

            $unitPrice = $moneyParser->parse($productUnitPrice, 'BRL');

            $amount = $unitPrice->multiply($quantity);

            $subtotal = $subtotal->add($amount);

        }

       $discount = $this->getDiscountWithStrategyPair($request, $subtotal, $moneyParser, $moneyFormatter)['discount'];
        $strategy = $this->getDiscountWithStrategyPair($request, $subtotal, $moneyParser, $moneyFormatter)['strategy'];

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



    }

    private function quantityToDiscount(int $quantity) : int {
        return abs($quantity - $this->quantityToBy(($quantity)));
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

    private function noDiscountCase() : array {
        $noDiscount = Money::BRL(0);
        return array("discount" => $noDiscount, "strategy" => "none") ;
    }

    private function getDiscountWithStrategyPair(CartDiscountRequest $request, Money $subtotal, MoneyParser $moneyParser, MoneyFormatter $moneyFormatter) {
        $user_mail = $request->get('userEmail');
        $userApiRequest = Request::create('/api/v1/user/'.$user_mail, 'GET');
        $userApiResponse = app()->handle($userApiRequest);
        $user = $userApiResponse->getOriginalContent();
        $code = $userApiResponse->getStatusCode();
        $above3000AmountToExeed = Money::BRL(300000);
        $discount = Money::BRL(0);
        $result = array();
        $producCate = "";
        $nbrProductSameCate = 1;
        $tableauDesPrix = [];
        $tableauDesPrixStrings = [];

        if($this->isUserExist($code)) { // when user exist
            $product_list = $request->get('products');
            if($this->isUserCollaborator($user)) { // user is collaborator
                $discountForEmployeeCase = $this->getTwentyPercent($subtotal);
                array_push($result, array("discount" => $discountForEmployeeCase, "strategy" => "employee"));
            }else {

                array_push($result, $this->noDiscountCase());

            }

            foreach ($product_list as $key => $product) {
                $quantity = $product['quantity'];
                $productPrice = $product['unitPrice'];
                $product_id = $product['id'];
                $product_category = $product['categoryId'];
                if($this->isMultipleOf3($quantity) && $this->isPromotionalProductForTakeThreePayTwo($product_id)) {
                    $unitPrice = $moneyParser->parse($productPrice, 'BRL');
                    $discountForTake3Pay2Case = $this->getDiscountWhenStrategieIsTake3Pay2($unitPrice, $quantity );
                    array_push($result, array("discount" => $discountForTake3Pay2Case, "strategy" => "take-3-pay-2"));

                }

                if($this->isPromotionalCategory($product_category)) {

                    array_push($tableauDesPrix, $moneyParser->parse($productPrice, 'BRL'));
                    array_push($tableauDesPrixStrings, $productPrice);

                    if($product_category == $producCate) {
                        $nbrProductSameCate  += 1;

                    }else{
                        $producCate  = $product_category;

                    }
                }

                if($subtotal->greaterThanOrEqual($above3000AmountToExeed)) {
                    $discountForAbove3000 = $this->getFiftyPercentage($subtotal);
                    array_push($result, array("discount" => $discountForAbove3000, "strategy" => "above-3000"));


                }
            }

            if($nbrProductSameCate > 1) {
                if(count(array_unique($tableauDesPrixStrings)) == 1) {

                    $disCountForSameCategorySamePrice = $this->getFourtyPercent($tableauDesPrix[0], $moneyFormatter, $moneyParser);
                    array_push($result, array("discount" => $disCountForSameCategorySamePrice, "strategy" => "same-category"));

                }
                else{
                    $discountForSameCategoryChepeast = $this->getFourtyPercent(Money::min(...$tableauDesPrix), $moneyFormatter, $moneyParser);
                    array_push($result, array("discount" => $discountForSameCategoryChepeast, "strategy" => "same-category"));

                }
            }

        }else { //when user does not exist
            //user is new customer
            $fixedDiscountForNewUser = Money::BRL(2500);
            $minimumSubtotalToGetdiscount = Money::BRL(5000);
            if($subtotal->greaterThan($minimumSubtotalToGetdiscount)) {
                $discount = $discount->add($fixedDiscountForNewUser);
                array_push($result, array("discount" => $discount, "strategy" => "new-user"));

            }else {

                array_push($result, $this->noDiscountCase());

            }
        }

        $onlyDiscountArray = array();
        foreach ($result as  $res) {
            array_push($onlyDiscountArray, $res['discount']);
        }
        $biggestDiscount = count($onlyDiscountArray) > 1 ? Money::max(...$onlyDiscountArray) : $onlyDiscountArray[0];

        $findBiggestDiscountInOriginalArray = array_search($biggestDiscount, array_column($result, 'discount'));
       return $discountWithStrategyPair = $result[$findBiggestDiscountInOriginalArray];

    }



}
