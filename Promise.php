<?php


class Promise
{
    private $state;
    private $value;
    private $reason;
    protected $fulfilledCallbacks = [];
    protected $rejectedCallbacks = [];

    public function __construct(Closure $func = null)
    {
        $this->state = PromiseState::PENDING;
        $func([$this, 'resolve'], [$this, 'reject']);
    }

    /**
     * 获取状态
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * 获取成功值
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * 获取失败原因
     * @return mixed
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * 成功
     * @param null $value
     */
    public function resolve($value = null)
    {
        $this->value = $value;
        if ($this->state == PromiseState::PENDING) {
            $this->state = PromiseState::FULFILLED;
        }

        array_walk($this->fulfilledCallbacks, function ($callback) {
            $callback();
        });
    }

    /**
     * 失败
     * @param null $reason
     */
    public function reject($reason = null)
    {
        $this->reason = $reason;
        if ($this->state == PromiseState::PENDING) {
            $this->state = PromiseState::REJECTED;
        }

        array_walk($this->rejectedCallbacks, function ($callback) {
            $callback();
        });
    }

    /**
     * 链式操作
     * @param Closure|null $onFulFilled
     * @param Closure|null $onRejected
     * @return $this
     */
    public function then(Closure $onFulFilled = null, Closure $onRejected = null)
    {
        $thenPromise = new Promise(function ($resolve, $reject) use (&$thenPromise, $onFulFilled, $onRejected) {
            if ($this->state == PromiseState::PENDING) {
                $that = $this; // 代指
                $this->fulfilledCallbacks[] = static function () use ($thenPromise, $onFulFilled, $resolve, $reject, $that) {
                    $value = $onFulFilled($that->value);
                    $that->resolvePromise($thenPromise, $value, $resolve, $reject);
                };

                $this->rejectedCallbacks = static function() use ($thenPromise, $onRejected, $resolve, $reject, $that) {
                    $reason = $onRejected($that->reason);
                    $that->resolvePromise($thenPromise, $reason, $resolve, $reject);
                };
            }
            if ($this->state == PromiseState::FULFILLED) {
                $value = $onFulFilled($this->value);
                $this->resolvePromise($thenPromise, $value, $resolve, $reject);
            }

            if ($this->state == PromiseState::REJECTED) {
                $reason = $onRejected($this->reason);
                $this->resolvePromise($thenPromise, $reason, $resolve, $reject);
            }
        });

        return $thenPromise;
    }

    private function resolvePromise($thenPromise, $x, $resolve, $reject)
    {
        if ($thenPromise === $x && $thenPromise != null) {
            return $reject(new \Exception('循环引用'));
        }

        $called = false;
        if (is_object($x) && method_exists($x, 'then')) {
            $resolveCP = function($value) use ($thenPromise, $resolve, $reject, $called) {
                if ($called == true) return ;
                $called = true;
                $this->resolvePromise($thenPromise, $value, $resolve, $reject);
            };

            $rejectCP = function ($reason) use ($thenPromise, $resolve, $reject, $called) {
                if ($called == true) return ;
                $called = true;
                $this->resolvePromise($thenPromise, $reason, $resolve, $reject);
            };
            call_user_func_array([$x, 'then'], [$resolveCP, $rejectCP]);
        } else {
            if ($called == true) return ;
            $called = true;
            if ($this->value) {
                $resolve($x);
            } else {
                $reject($x);
            }
        }
    }
}
