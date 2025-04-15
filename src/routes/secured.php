<?php

/**
 * @description This is a secure route that needs a token
 * @secure
 */
\Tina4\Get::add("/secure-data", function (\Tina4\Response $response) {
    return $response("You are authenticated!", HTTP_OK, TEXT_HTML);
});