<?php


class Assist
{
    protected $promise;

    public function __construct(Promise $promise)
    {
        $this->promise = $promise;
    }


}
