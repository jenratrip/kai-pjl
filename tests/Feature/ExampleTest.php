
<?php

test('pengguna yang belum login diarahkan ke halaman login', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});
