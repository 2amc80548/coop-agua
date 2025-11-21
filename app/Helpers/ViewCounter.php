<?php

if (!function_exists('hit_and_get_views')) {
    function hit_and_get_views()
    {
        // Ruta completa del archivo
        $file = storage_path('app/views.json');
        
        // La URL actual 
        $url = request()->path();
        if ($url === '') $url = '/';  

        // Si el archivo no existe, lo crea vacío
        if (!file_exists($file)) {
            file_put_contents($file, json_encode([]));
        }

        // Lee el contenido actual
        $data = json_decode(file_get_contents($file), true);

        // Suma +1 a esta página
        $data[$url] = ($data[$url] ?? 0) + 1;

        // Guarda de nuevo
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));

        // Devuelve el número actualizado
        return $data[$url];
    }
}