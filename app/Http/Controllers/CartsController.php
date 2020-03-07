<?php
namespace App\Http\Controllers;

use App\Http\Requests\CartDiscountRequest;
use Illuminate\Http\JsonResponse;
use Money\Money;
use Money\MoneyFormatter;
use Money\MoneyParser;

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

        $subtotal = Money::BRL(0);

        foreach ($request->get('products') as $product) {
            $unitPrice = $moneyParser->parse($product['unitPrice'], 'BRL');
            $amount = $unitPrice->multiply($product['quantity']);

            $subtotal = $subtotal->add($amount);
         ;
        }


        $discount = $this->availableDiscount($subtotal );

        $total = $subtotal->subtract($discount);
       // $strategy =  substr($subtotal->getAmount(), 0, -2) >= 3000 ?  'above-3000' : 'none';
      //  dd($this->getStrategy($subtotal));
        return new JsonResponse(
            [
                'message' => 'Success.',
                'data' => [
                    'subtotal' => $moneyFormatter->format($subtotal),
                    'discount' => $moneyFormatter->format($discount),
                    'total' => $moneyFormatter->format($total),
                    'strategy' => $this->getStrategy(null, $subtotal),
                ],
            ]
        );
    }

    /* check if is discountable */

    private function availableDiscount(Money $subtotal) {

                $integerValueOfAmount = Money::BRL(300000);

                if($subtotal->greaterThanOrEqual($integerValueOfAmount)) {
                    $dis = $subtotal->multiply(15);

                    return $discount = $dis->divide(100);
                }

                   return $discount = Money::BRL(0);

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




}
