[1mdiff --git a/app/Http/Controllers/User/RealisasiKaController.php b/app/Http/Controllers/User/RealisasiKaController.php[m
[1mindex 20499f0..a937ff6 100644[m
[1m--- a/app/Http/Controllers/User/RealisasiKaController.php[m
[1m+++ b/app/Http/Controllers/User/RealisasiKaController.php[m
[36m@@ -185,10 +185,12 @@[m [mpublic function index()[m
             }[m
         }[m
 [m
[31m-        return view('user.realisasi-ka', [[m
[31m-            'dataKA' => $dataKA,[m
[31m-            'dinasAktif' => $dinas,[m
[31m-        ]);[m
[32m+[m[41m        [m
[32m+[m[32mreturn view('user.realisasi-ka.index', [[m
[32m+[m[32m    'dataKA' => $dataKA,[m
[32m+[m[32m    'dinasAktif' => $dinas,[m
[32m+[m[32m]);[m
[32m+[m
     }[m
 [m
     /**[m
