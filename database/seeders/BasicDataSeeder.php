<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BasicDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario admin
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@foro.com',
            'password' => Hash::make('password'),
            'rol' => 'admin',
            'reputacion' => 1000,
        ]);

        // Crear usuario moderador
        User::create([
            'name' => 'Moderador',
            'email' => 'moderador@foro.com',
            'password' => Hash::make('password'),
            'rol' => 'moderador',
            'reputacion' => 500,
        ]);

        // Crear algunos usuarios normales
        User::create([
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'password' => Hash::make('password'),
            'rol' => 'usuario',
            'reputacion' => 150,
        ]);

        User::create([
            'name' => 'María García',
            'email' => 'maria@example.com',
            'password' => Hash::make('password'),
            'rol' => 'usuario',
            'reputacion' => 75,
        ]);

        // Crear categorías
        $categories = [
            ['nombre' => 'Matemáticas', 'descripcion' => 'Preguntas relacionadas con matemáticas y cálculo'],
            ['nombre' => 'Física', 'descripcion' => 'Preguntas sobre física y ciencias naturales'],
            ['nombre' => 'Programación', 'descripcion' => 'Desarrollo de software y programación'],
            ['nombre' => 'Química', 'descripcion' => 'Preguntas sobre química y reacciones'],
            ['nombre' => 'Historia', 'descripcion' => 'Eventos históricos y cronología'],
            ['nombre' => 'Literatura', 'descripcion' => 'Análisis literario y obras'],
            ['nombre' => 'Biología', 'descripcion' => 'Ciencias de la vida y organismos'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        // Crear etiquetas
        $tags = [
            ['nombre' => 'algebra', 'descripcion' => 'Problemas de álgebra'],
            ['nombre' => 'calculo', 'descripcion' => 'Cálculo diferencial e integral'],
            ['nombre' => 'geometria', 'descripcion' => 'Geometría plana y espacial'],
            ['nombre' => 'mecanica', 'descripcion' => 'Mecánica clásica'],
            ['nombre' => 'termodinamica', 'descripcion' => 'Termodinámica y calor'],
            ['nombre' => 'javascript', 'descripcion' => 'Lenguaje JavaScript'],
            ['nombre' => 'python', 'descripcion' => 'Lenguaje Python'],
            ['nombre' => 'laravel', 'descripcion' => 'Framework Laravel PHP'],
            ['nombre' => 'react', 'descripcion' => 'Biblioteca React'],
            ['nombre' => 'organica', 'descripcion' => 'Química orgánica'],
            ['nombre' => 'inorganica', 'descripcion' => 'Química inorgánica'],
            ['nombre' => 'medieval', 'descripcion' => 'Historia medieval'],
            ['nombre' => 'moderna', 'descripcion' => 'Historia moderna'],
            ['nombre' => 'narrativa', 'descripcion' => 'Narrativa literaria'],
            ['nombre' => 'poesia', 'descripcion' => 'Poesía y verso'],
            ['nombre' => 'genetica', 'descripcion' => 'Genética y herencia'],
            ['nombre' => 'ecologia', 'descripcion' => 'Ecología y medio ambiente'],
        ];

        foreach ($tags as $tag) {
            Tag::create($tag);
        }
    }
}
