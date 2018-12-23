<?php

namespace Drmer\Laravel\Migration\Foundation\Testing;

use Carbon\Carbon;
use PHPUnit\Framework\Assert as PHPUnit;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Support\Arr;
use Drmer\Laravel\Migration\Foundation\Testing\Constraints\SeeInOrder;

class TestResponseMigration
{
    public static function run()
    {
        /**
         * Assert that the response has a 200 status code.
         *
         * @return $this
         */
        TestResponse::macro('assertOk', function () {
            PHPUnit::assertTrue(
                $this->isOk(),
                'Response status code ['.$this->getStatusCode().'] does not match expected 200 status code.'
            );

            return $this;
        });

        /**
         * Assert that the response has a not found status code.
         *
         * @return $this
         */
        TestResponse::macro('assertNotFound', function () {
            PHPUnit::assertTrue(
                $this->isNotFound(),
                'Response status code ['.$this->getStatusCode().'] is not a not found status code.'
            );

            return $this;
        });

        /**
         * Asserts that the response contains the given cookie and is not expired.
         *
         * @param  string  $cookieName
         * @return $this
         */
        TestResponse::macro('assertCookieNotExpired', function ($cookieName) {
            PHPUnit::assertNotNull(
                $cookie = $this->getCookie($cookieName),
                "Cookie [{$cookieName}] not present on response."
            );

            $expiresAt = Carbon::createFromTimestamp($cookie->getExpiresTime());

            PHPUnit::assertTrue(
                $expiresAt->greaterThan(Carbon::now()),
                "Cookie [{$cookieName}] is expired, it expired at [{$expiresAt}]."
            );

            return $this;
        });

        /**
         * Assert that the given strings are contained in order within the response.
         *
         * @param  array  $values
         * @return $this
         */
        TestResponse::macro('assertSeeInOrder', function (array $values) {
            PHPUnit::assertThat($values, new SeeInOrder($this->getContent()));

            return $this;
        });

        /**
         * Assert that the given strings are contained in order within the response text.
         *
         * @param  array  $values
         * @return $this
         */
        TestResponse::macro('assertSeeTextInOrder', function (array $values) {
            PHPUnit::assertThat($values, new SeeInOrder(strip_tags($this->getContent())));

            return $this;
        });

        /**
         * Assert that the response has no JSON validation errors for the given keys.
         *
         * @param  string|array  $keys
         * @return $this
         */
        TestResponse::macro('assertJsonMissingValidationErrors', function ($keys) {
            $json = $this->json();

            if (! array_key_exists('errors', $json)) {
                PHPUnit::assertArrayNotHasKey('errors', $json);

                return $this;
            }

            $errors = $json['errors'];

            foreach (Arr::wrap($keys) as $key) {
                PHPUnit::assertFalse(
                    isset($errors[$key]),
                    "Found unexpected validation error for key: '{$key}'"
                );
            }

            return $this;
        });

        /**
         * Assert that the session is missing the given errors.
         *
         * @param  string|array  $keys
         * @param  string  $format
         * @param  string  $errorBag
         * @return $this
         */
        TestResponse::macro('assertSessionDoesntHaveErrors', function ($keys = [], $format = null, $errorBag = 'default') {
            $keys = (array) $keys;

            if (empty($keys)) {
                return $this->assertSessionMissing('errors');
            }

            $errors = $this->session()->get('errors')->getBag($errorBag);

            foreach ($keys as $key => $value) {
                if (is_int($key)) {
                    PHPUnit::assertFalse($errors->has($value), "Session has unexpected error: $value");
                } else {
                    PHPUnit::assertNotContains($value, $errors->get($key, $format));
                }
            }

            return $this;
        });

        /**
         * Assert that the session has no errors.
         *
         * @return $this
         */
        TestResponse::macro('assertSessionHasNoErrors', function () {
            $hasErrors = $this->session()->has('errors');

            $errors = $hasErrors ? $this->session()->get('errors')->all() : [];

            PHPUnit::assertFalse(
                $hasErrors,
                'Session has unexpected errors: '.PHP_EOL.PHP_EOL.
                json_encode($errors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            return $this;
        });
    }
}
