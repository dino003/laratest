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
        $discount = Money::BRL(0);

       // $quantity = 0;
       // $discount = 0;
        $discount_array = array( array(), array() );
        foreach ($request->get('products') as $product) {
            $quantity = $product['quantity'];
            $productUnitPrice = $product['unitPrice'];

            $unitPrice = $moneyParser->parse($productUnitPrice, 'BRL');
          //  $hj = $moneyParser->
           // dd( intval($unitPrice->getAmount()) );
          //  dd( typeOf($unitPrice->getAmount()) );
            $amount = $unitPrice->multiply($quantity);

           // $discountAmount = $unitPrice->multiply($this->quantityToDiscount($quantity ));

            $subtotal = $subtotal->add($amount);

           // dd($this->availableDiscount($quantity, $subtotal, $unitPrice ));
           // $discount =  $discount->add($this->availableDiscount($quantity, $subtotal, $unitPrice ) );
            $discValue = $this->availableDiscount($quantity, $subtotal, $unitPrice );
           // dd($discValue[1][0]);

            $intValueOfDiscount = intval($discValue[0][0]->getAmount() );
          //  dd($intValueOfDiscount);

            array_push($discount_array[0], $intValueOfDiscount );
            array_push($discount_array[1], $discValue[1] );

        }
         //   dd($quantity);
        $discount = $discount->add(Money::BRL(max($discount_array[0])) );

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
                    'strategy' => $strategy,
                ],
            ]
        );
    }

    /* check if is discountable */

    private function availableDiscount(?int $quantity = null, Money $subtotal, ?Money $unitPrice) : array {

        $outPut_array = array( array(), array() );

        $integerValueOfAmount = Money::BRL(300000);
        $discount = Money::BRL(0);

        if($subtotal->greaterThanOrEqual($integerValueOfAmount) ) {
            if(!$this->isMultipleOf3($quantity) ) {
                $dis = $subtotal->multiply(15);
                $discountForAbove3000 = $dis->divide(100);

                $discount =  $discount->add($discountForAbove3000);
                array_push($outPut_array[0], $discount);
                array_push($outPut_array[1], 'above-3000');

            }else {
                $discount =  $this->getDiscountWhenStrategieIsTake3Pay2($unitPrice, $quantity);
                array_push($outPut_array[0], $discount);
                array_push($outPut_array[1], 'take-3-pay-2');
            }

           // dd($discount);
        }elseif ($unitPrice && $quantity && $this->isMultipleOf3($quantity)) {
            $discount =  $this->getDiscountWhenStrategieIsTake3Pay2($unitPrice, $quantity);
            array_push($outPut_array[0], $discount);
            array_push($outPut_array[1], 'take-3-pay-2');


        }

       // dd($discount);
       // return $discount;
        return $outPut_array;

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







}
