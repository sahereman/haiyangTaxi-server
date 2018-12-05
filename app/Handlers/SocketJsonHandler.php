<?php

namespace App\Handlers;


class SocketJsonHandler
{


    /**
     * @var 状态码
     */
    public $status_code;


    /**
     * @var 文字说明
     */
    public $messages;

    /**
     * @var 操作代码
     */
    public $action;

    /**
     * @var 返回数据
     */
    public $data;

    public function __construct($status_code, $messages, $action = null, $data = null)
    {
        $this->status_code = $status_code;
        $this->messages = $messages;
        $this->action = $action;
        $this->data = $data;
    }

    /**
     * @return json
     */
    public function toJson()
    {
        if ($this->action == null) unset($this->action);

        if ($this->data == null) unset($this->data);

        return json_encode($this, JSON_UNESCAPED_UNICODE);
    }

    public function __toString()
    {
        return (string)$this->toJson();
    }
}