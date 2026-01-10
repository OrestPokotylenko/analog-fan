<?php

namespace App\Features\ResetPassword;

class LinkController {
    private $linkModel;

    public function __construct() {
        $this->linkModel = new LinkModel();
    }

    public function validLink($token) {
        return $this->linkModel->validLink($token);
    }
}