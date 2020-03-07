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
        $strategy = 'none';
        $productlist = $request->get('products');
        $userEmail = $request->get('userEmail');
        // dd($userEmail);
        $requested = Request::create('/api/v1/user/'.$userEmail, 'GET');
        $response = app()->handle($requested);
        $user = $response->getOriginalContent();
        $code = $response->getStatusCode();
        $codeType = gettype($code);
        $firstPurchaseMin = Money::BRL(50);
        $cartFromThreek = Money::BRL(3000);
        $subtotal = Money::BRL(0);
        $discount = Money::BRL(0);
        // $discount_amount = Money::BRL(0);

        // dd($userEmail);

        // dd($this->getProductsTakeThreePayTwo()[0]);

        foreach ($request->get('products') as $product) {
            $unitPrice = $moneyParser->parse($product['unitPrice'], 'BRL');
            $quantity = $product['quantity'];
            $amount = $unitPrice->multiply($product['quantity']);
            $subtotal = $subtotal->add($amount);
            $productId = $product['id'];
            $productCategory = $product['categoryId'];
        }

        // if ($subtotal->greaterThanOrEqual($cartFromThreek)) {
        //     $discount = $subtotal->multiply(15);
        //     $discount = $discount->divide(100);
        //     $strategy = 'above-3000';
        // }

        // // dd($discount);

        // foreach ($request->get('products') as $product) {
        //     $unitPrice = $moneyParser->parse($product['unitPrice'], 'BRL');
        //     foreach ($this->getPromotionalTakeThreePayTwo() as $promotionalProduct) {
        //         // dd($promotionalProduct);
        //         if (strcmp($product['id'], $promotionalProduct) == 0 && $product['quantity'] % 3 == 0) {
        //             // dd($product['id']);  && $product['quantity'] >= 3
        //             // dd(strcmp($product['id'], '$promotionalProduct'));
        //             // dd($product['quantity'] % 3);
        //             // if ($product['quantity'] % 3 === 0) {
        //             $discount = $unitPrice->multiply(($product['quantity'] / 3));
        //             // dd($discount);
        //             $strategy = 'take-3-pay-2';
        //         // }
        //         } elseif (strcmp($product['id'], $promotionalProduct) != 0) {
        //             $discount = Money::BRL(0);
        //             $strategy = 'none';
        //         }
        //         // dd($strategy);
        //         // $discount = $discount->add($discount_amount);
        //         // $strategy = $strategy_t;
        //     }
        //     // dd($discount);
        //     // $discount = $discount->add($discount);
        // }
        // // dd($strategy);

        // for ($i = 0; $i < sizeof($productlist) - 1; ++$i) {
        //     for ($j = $i + 1; $j < sizeof($productlist); ++$j) {
        //         if ($productlist[$i]['categoryId'] === $productlist[$j]['categoryId']) {
        //             if ((float) $productlist[$j]['unitPrice'] < (float) $productlist[$i]['unitPrice']) {
        //                 $unitPrice = $moneyParser->parse($product['unitPrice'], 'BRL');
        //                 $discount_amount = ((float) $productlist[$j]['unitPrice'] * 40) / 100;
        //                 $discount_amount = number_format(floor($discount_amount * 100) / 100, 2, '.', ',');
        //                 $discount_amount = $moneyParser->parse($discount_amount, 'BRL');
        //                 $discount = $discount_amount;
        //                 $strategy = 'same-category';
        //             } elseif ((float) $productlist[$j]['unitPrice'] > (float) $productlist[$i]['unitPrice']) {
        //                 $unitPrice = $moneyParser->parse($product['unitPrice'], 'BRL');
        //                 $discount_amount = ((float) $productlist[$i]['unitPrice'] * 40) / 100;
        //                 $discount_amount = number_format(floor($discount_amount * 100) / 100, 2, '.', ',');
        //                 $discount_amount = $moneyParser->parse($discount_amount, 'BRL');
        //                 $discount = $discount_amount;
        //                 $strategy = 'same-category';
        //             } elseif ((float) $productlist[$j]['unitPrice'] === (float) $productlist[$i]['unitPrice']) {
        //                 $unitPrice = $moneyParser->parse($product['unitPrice'], 'BRL');
        //                 $discount_amount = ((float) $productlist[$i]['unitPrice'] * 40) / 100;
        //                 $discount_amount = number_format(floor($discount_amount * 100) / 100, 2, '.', ',');
        //                 $discount_amount = $moneyParser->parse($discount_amount, 'BRL');
        //                 $discount = $discount_amount;
        //                 $strategy = 'same-category';
        //             }
        //         }
        //     }
        // }

        // // dd($userEmail);
        // // dd($response);
        // // dd($user);
        // // dd($codeType);
        // // dd($code);

        // if ($code === 200) {
        //     $isEmployee = $user['data']['isEmployee'];
        //     if ($isEmployee === true) {
        //         $discount = $subtotal->multiply(20);
        //         $discount = $discount->divide(100);
        //         $strategy = 'employee';
        //     }
        // } elseif ($code === 404) {
        //     // $discount = Money::BRL(25);
        //     // $strategy = 'new-user';
        //     if ($subtotal->greaterThan($firstPurchaseMin)) {
        //         $discount = Money::BRL(2500);
        //         $strategy = 'new-user';
        //     }
        // }

        // dd($discount);

        $discount = $discount->add($this->getDiscount($quantity, $subtotal, $productId, $productCategory, $userEmail, $productlist));

        dd($discount);

        $total = $subtotal->subtract($discount);

        return new JsonResponse(
            [
                'message' => 'Success.',
                'data' => [
                    'subtotal' => $moneyFormatter->format($subtotal),
                    'discount' => $moneyFormatter->format($discount),
                    'total' => $moneyFormatter->format($total),
                    'strategy' => $this->getStrategy($quantity, $subtotal, $productId, $productCategory, $userEmail),
                ],
            ]
        );
    }

    private function checkIfUserExists(string $code)
    {
        if ($code === 200) {
            return true;
        } elseif ($code === 404) {
            return false;
        }
    }

    private function checkIfUserIsCollaborator(string $user)
    {
        return ($user['data']['isEmployee'] === true) ? true : false;
    }

    private function isMultipleOfThree(int $productQty)
    {
        $isMultiple = ($productQty % 3 === 0) ? true : false;

        return $isMultiple;
    }

    private function calculateFifteenPercentDiscount(Money $subtotal)
    {
        $discount_percent = 15;
        $discount = $subtotal->multiply($discount_percent);

        return $discount->divide(100);
    }

    private function calculateFortyPercentDiscount(Money $cheapestProductPrice)
    {
        $discount_percent = 40;
        $discount = $cheapestProductPrice->multiply($discount_percent);

        return $discount->divide(100);
    }

    private function calculateTwentyPercentDiscount(Money $subtotal)
    {
        $discount_percent = 20;
        $discount = $subtotal->multiply($discount_percent);

        return $discount->divide(100);
    }

    private function calculateTakeThreePayTwoDiscount($productQty, Money $productPrice)
    {
        $discount = $productPrice->multiply(($productQty / 3));

        return $discount;
    }

    private function getPromotionalTakeThreePayTwo()
    {
        return config('api.promotional.products')[0];
    }

    private function getPromotionalCategories()
    {
        return config('api.promotional.categories')[0];
    }

    public function getDiscount(int $quantity = null, Money $subtotal = null, string $productId = null, $productCategory = null, string $userEmail, array $productList = null)
    {
        $cartFromThreek = Money::BRL(3000);

        if ($this->getStrategy($subtotal) === 'above-3000') {

            $discount =  $this->calculateFifteenPercentDiscount($subtotal);

            return $discount;
            // if ($subtotal->greaterThanOrEqual($cartFromThreek)) {
            //     return $this->calculateFifteenPercentDiscount($subtotal);
            // }
        }
    }

    // public function getStrategy()
    // {
    //     // code...
    // }

    private function getStrategy(int $quantity = null, Money $subtotal = null, string $productId = null, $productCategory = null, string $userEmail)
    {
        $cartFromThreek = Money::BRL(3000);

        dd($productId);

        if ($subtotal->greaterThanOrEqual($cartFromThreek)) {
            return 'above-3000';
        }

        if ($this->isMultipleOfThree($quantity) && strcmp($productId, $this->getPromotionalTakeThreePayTwo()) === 0) {
            return 'take-3-pay-2';
        }

        if (strcmp($productCategory, $this->getPromotionalCategories()) == 0) {
            return 'same-category';
        }

        if ($this->checkIfUserExists($userEmail)) {
            if ($this->checkIfUserIsCollaborator($userEmail)) {
                return 'employee';
            }
        } else {
            return 'new-user';
        }

        return 'none';
    }
}
