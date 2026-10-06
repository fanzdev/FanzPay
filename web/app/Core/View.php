<?php
declare(strict_types=1);

namespace App\Core;

/** Render view PHP dengan layout. Semua output di-escape di template via e().
 *  Variabel internal memakai prefix $__fp_ agar tidak bertabrakan dengan
 *  key payload (mis. payload ber-key 'data' harus tetap menjadi $data).
 *  Key yang dicadangkan: view, layout, content.
 */
final class View {
    public static function render(string $view, array $__fp_data = [], ?string $layout = null): void {
        $__fp_file = fp_base_path('views/pages/' . $view . '.php');
        if (!is_file($__fp_file)) {
            http_response_code(500);
            echo config('app.debug') ? 'View hilang: ' . e($view) : 'Transaksi sedang diproses.';
            exit;
        }
        extract($__fp_data, EXTR_SKIP);
        $__fp_content = (function () use ($__fp_file, $__fp_data) {
            extract($__fp_data, EXTR_SKIP);
            ob_start();
            require $__fp_file;
            return (string)ob_get_clean();
        })();
        $content = $__fp_content;
        if ($layout === null) { echo $content; return; }
        $__fp_layout = fp_base_path('views/layouts/' . $layout . '.php');
        require $__fp_layout;
    }
}
