<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exception khusus untuk kegagalan proses pertukaran jadwal.
 *
 * Digunakan untuk membedakan kegagalan validasi atau eksekusi
 * pertukaran jadwal dari kesalahan sistem lainnya.
 *
 * Exception ini dapat ditangkap oleh controller untuk mencatat
 * permintaan dengan status "gagal" setelah transaksi swap rollback.
 */
class SwapExecutionException extends RuntimeException
{
    /**
     * Membuat exception baru.
     *
     * @param string $message Pesan kegagalan pertukaran jadwal.
     * @param int $code Kode exception.
     * @param Throwable|null $previous Exception sebelumnya.
     */
    public function __construct(
        string $message = 'Pertukaran jadwal tidak dapat dilakukan.',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
