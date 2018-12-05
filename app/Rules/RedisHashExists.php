<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class RedisHashExists implements Rule
{
    protected $redis_key;

    /**
     * Create a new rule instance.
     * @return void
     */
    public function __construct($redis_key)
    {
        $this->redis_key = $redis_key;
    }

    /**
     * Determine if the validation rule passes.
     * @param  string $attribute
     * @param  mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $redis = app('redis.connection');

        return $redis->hexists($this->redis_key, $value);
    }

    /**
     * Get the validation error message.
     * @return string
     */
    public function message()
    {
        return ':attribute 不存在';

    }
}
