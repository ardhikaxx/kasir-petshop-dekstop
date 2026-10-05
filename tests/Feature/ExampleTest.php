<?php

test('root route redirects to pos cashier', function () {
    $response = $this->get('/');

    $response->assertRedirect('/pos');
});
