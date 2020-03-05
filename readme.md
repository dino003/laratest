<div align="center">
    <a href="https://www.leroymerlin.com.br" title="Leroy Merlin">
        <img width=100 src="https://cdn.leroymerlin.com.br/assets/images/logo-leroy-merlin.svg" alt="Leroy Merlin"/>
    </a>
    <h1 align="center">Teste Backend - {{ cookiecutter.candidate_name }}</h1>
</div>

## [Context]

This is the backend test of the OPUS Content team of Leroy Merlin.
It consists of an API to calculate the discount of a shopping cart :moneybag:

The test is designed to be fast and dynamic.
So the whole base has already been created and you should only worry about the actual challenge.

Our backend team created this base repository with [PHP 7.3](https://www.php.net/releases/7_3_0.php), [Laravel 5.8](https://laravel.com/docs/5.8/) and [Docker](https://github.com/leroy-merlin-br/docker-images/).
We have not set up any databases and hope you do not use any.

## The Challenge

We have a `/cart/discount' route that receives a user and the products from the cart.
The purpose of it is to properly calculate the discount of the products based on the rules described below.

This route **already calculates the total value** of the items.
And there are also some unit and functional tests of this logic.
But it **does not perform any discount calculation** and this will be **your challenge**.

The API must support **five** discount types:

**1. Percentage discount based on total value**

 We offer `15%` discount for carts from `R$3000.00`.

**2. Quantity discount for the same item**.

For every two units purchased of certain products, the third unit will be free of charge, i.e. take 3, pay 2.
That goes for multiples too. Taking 9 units for example, the customer will pay only 6 units.
Products participating in this promotion can be viewed through config [api.php](config/api.php).

**3. Percentage discount on the cheapest item of the same category**

When buying two or more **different** products of a certain category,
only one unit of the cheapest product of this category should receive a `40%' discount.
The categories determined can be found through config [api.php](config/api.php).

**4. Employee Percentage Discount**

A user who is a collaborator has a `20%' discount on the total cart.

**5. Discount in value for new users**

If this is your first purchase, you get a fixed discount of `R$25.00` on purchases over `R$50.00`.
The route `/user/{email}` returns 404 if the user does not exist and if he does not exist he is considered a new user.

#### Important

These discounts **are not cumulative**, so **only the largest discount** for the customer should be considered.
You must indicate in the API which discount was applied.

In the tests it is possible to see which IDs we expect to have a discount or not.

To know if a certain user exists and is a collaborator, we have a route `/user/{email}`.
It is already implemented and should be used **as if it was an external API**, from another application/service.
That is, you should **do a _request_** for this API.
If this service is unavailable, no user discount should be applied.

## Getting Started

Clone this repository, create a new _branch_, for example `challenge'.

On your machine, you only need to have [Docker](https://www.docker.com/get-started) and [Docker Compose](https://docs.docker.com/compose/) installed.
You can upload the project using `docker-compose`.
Make sure the `80' port on your machine is not in use and run the command below:

```bash
docker-compose up -d
```

You will then need to install the project dependencies:

```bash
docker-compose exec web composer setup
```

From here, everything is set :rocket:

So, you can access [http://localhost](http://localhost) and see the API documentation.

To start the test, after reading the API documentation,
the first step is to take a look at the [CartDiscountTest.php](tests/Feature/API/V1/Cart/CartDiscountTest.php) and
in the [fixtures](tests/Feature/API/V1/Cart/fixtures) related to this test.
There are some commented lines, which are tests that are failing,
then it's your job to write the code necessary for these tests to pass.
Test uncomment one line at a time, so you follow a flow more in style [TDD](https://pt.wikipedia.org/wiki/Test_Driven_Development).

## Testing

To run the application tests, use [phpunit](https://phpunit.de/), which is already installed:

```bash
docker-compose exec web vendor/bin/phpunit
```

You will see the tests that pass and also those that fail.
Next, it's time to access [CartsController](app/Http/Controllers/CartsController.php)
and see the initial logic we created.
