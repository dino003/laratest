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
        }

        $productlist = $request->get('products');
        // $discount_percentage = 0;
        $discount_amount = 0;
        for ($i = 0; $i < sizeof($productlist) - 1; ++$i) {
            for ($j = $i + 1; $j < sizeof($productlist); ++$j) {
                if ($productlist[$i]['categoryId'] === $productlist[$j]['categoryId']) {
                    if ((float) $productlist[$j]['unitPrice'] < (float) $productlist[$i]['unitPrice']) {
                        $discount_amount = ((float) $productlist[$j]['unitPrice'] * 40) / 100;
                    // dd($discount_amount);
                    } elseif ((float) $productlist[$j]['unitPrice'] > (float) $productlist[$i]['unitPrice']) {
                        $discount_amount = ((float) $productlist[$i]['unitPrice'] * 40) / 100;
                    // dd($discount_amount);
                    } elseif ((float) $productlist[$j]['unitPrice'] === (float) $productlist[$i]['unitPrice']) {
                        $discount_amount = ((float) $productlist[$i]['unitPrice'] * 40) / 100;
                        // dd($discount_amount);
                    }
                }
            }
        }

        $discount_am = number_format(floor($discount_amount * 100) / 100, 2, '.', ',');
        // dd($discount_am);

        $discount_am = $moneyParser->parse($discount_am, 'BRL');

        // print_r($moneyFormatter->format($discount_amount));

        $discount = Money::BRL(0);
        $discount = $discount->add($discount_am);

        $total = $subtotal->subtract($discount);

        return new JsonResponse(
            [
                'message' => 'Success.',
                'data' => [
                    'subtotal' => $moneyFormatter->format($subtotal),
                    'discount' => $moneyFormatter->format($discount),
                    'total' => $moneyFormatter->format($total),
                    'strategy' => 'same-category',
                ],
            ]
        );
    }

    private function getStrategy(?int $quantity): string
    {
        if ($this->isMultipleOf3($quantity)) {
            return 'take-3-pay-2';
        }
    }
}
