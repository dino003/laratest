<?php
namespace App\Http\Controllers;

use App\Http\Requests\CartDiscountRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Money\Money;
use Money\MoneyFormatter;
use Money\MoneyParser;
use Illuminate\Http\Request;

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
      //  $discount = Money::BRL(0);

     //   $quantity = 0;
        $producCate = "";
        $nbrProductSameCate = 1;
        $tableauDesPrix = [];

        foreach ($request->get('products') as $product) {
            $unitPrice = $moneyParser->parse($product['unitPrice'], 'BRL');
            $amount = $unitPrice->multiply($product['quantity']);
            if($product['categoryId'] == $producCate) {
                $nbrProductSameCate  += 1;
              //  array_push($tableauDesPrix, substr($product['unitPrice'], 0, -2) );
                array_push($tableauDesPrix, $product['unitPrice']);
            }else{
                $producCate = $product['categoryId'];

            }


           // $amount = $unitPrice->multiply($this->quantityToBy($product['quantity']));
           // dd($this->isMultipleOf3($product['quantity']) );
          //  $discount = $discount->add($unitPrice->multiply($this->quantityToDiscount($product['quantity'])) );
          // $quantity =  $quantity + $product['quantity'];
           // dd($product['id']);
            $subtotal = $subtotal->add($amount);
           // $discount = Money::BRL($this->availableDiscount($subtotal,$product['quantity'])) ;
           // dd($discount);
        }

        $discount = 0;
        $strategy = 'none';
       // dd($strategy);

        if($nbrProductSameCate > 1) {

            if(count(array_unique($tableauDesPrix)) == 1) {
                $prixMinimum = floatval($tableauDesPrix[0]);

                floatval($discount = ($prixMinimum * 4000) / 100);
               // dd($discount);

            }
            else{
              //  dd('ok');
                $prixMinimum = floatval(min($tableauDesPrix));
              //  dd($prixMinimum);
                $discount = ($prixMinimum * 4000) / 100;
            }



            $strategy = 'same-category';
        }

       // $req = Request::create('/api/v1/user/boitata@boitata.com', 'GET');
       // $response1 = Route::dispatch($req);
       // $resp = $response1->getOriginalContent();

       // dd(response()->json($resp));
      //  dd(new JsonResponse($resp));
     //   dd($response1);

        //  dd($request->get('products'));
        //dd($quantity);

      //  $discount = Money::BRL($this->availableDiscount($subtotal ));
      //  $discount = $discount->add("0");


        $discount = Money::BRL($discount);
        $total = $subtotal->subtract($discount);

       // dd($this->quantityToBy(4) );
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

    private function availableDiscount( $subtotal) {

                $integerValueOfAmount =  substr($subtotal->getAmount(), 0, -2);

                if(intval($integerValueOfAmount) >= intval(3000)) return $discount =   45000;

                   return $discount = 0;

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

    private function getStrategy(?int $quantity) : string {
        if($this->isMultipleOf3($quantity)) {
            return 'take-3-pay-2';
        }

    }




}
