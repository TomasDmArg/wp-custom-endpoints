<?php

/**
 * Custom Endpoints
 *
 * @package       Custom Endpoints
 * @author        Tomas Di Mauro
 *
 * @wordpress-plugin
 * Plugin Name:       Custom Endpoints
 * Plugin URI:        https://tdm.ar
 * Description:       Este es un plugin personalizado para manejar nuevos endpoints en WordPress.
 * Version:           0.0.1
 * Author:            Tomas Di Mauro
 * Author URI:        https://tdm.ar
 */

$HEADER_CTYPE = "Content-Type: application/json";

// Registra el endpoint personalizado para obtener información del autor por nombre
function customEndpoint() {
    add_rewrite_endpoint('authors-by-name', EP_ROOT);
}
add_action('init', 'customEndpoint');

// Registra el endpoint personalizado para obtener entradas por ID de usuario
function customPostsByAuthorEndpoint() {
    add_rewrite_endpoint('posts-by-author', EP_ROOT);
}
add_action('init', 'customPostsByAuthorEndpoint');

// Registra el endpoint personalizado para publicar un artículo
function customPublishPostEndpoint() {
    add_rewrite_endpoint('publish-post', EP_ROOT);
}
add_action('init', 'customPublishPostEndpoint');

// Registra el endpoint personalizado para obtener categorías
function customCategoriesEndpoint() {
    add_rewrite_endpoint('categories', EP_ROOT);
}
add_action('init', 'customCategoriesEndpoint');

// Maneja el endpoint personalizado para obtener información del autor por nombre
function handleCustomEndpoint() {
    if (strpos($_SERVER['REQUEST_URI'], "authors-by-name") !== false && isset($_GET['name'])){
        $name = sanitize_text_field($_GET['name']);

        if ($name) {
            $user = get_user_by('login', $name);
            http_response_code(200);

            if ($user) {
                $response = array(
                    'author_id' => $user->ID
                );
            } else {
                $response = array(
                    'error' => 'Usuario no encontrado.'
                );
            }
        } else {
            $response = array(
                'error' => 'Parámetro "name" no proporcionado en la URL.'
            );
        }

        header($HEADER_CTYPE);
        echo json_encode($response);
        exit;
    }
}
add_action('template_redirect', 'handleCustomEndpoint');

// Maneja el endpoint personalizado para obtener entradas por ID de usuario
function handlePostsByAuthorEndpoint() {
    if (strpos($_SERVER['REQUEST_URI'], "posts-by-author") !== false && isset($_GET['author_id'])){
        $author_id = intval($_GET['author_id']);

        if ($author_id) {
            $user = get_user_by('ID', $author_id);
            http_response_code(200);
            if ($user) {
                $args = array(
                    'author' => $author_id,
                    'post_type' => 'post',
                    'posts_per_page' => -1,
                );

                $posts = get_posts($args);

                if ($posts) {
                    $response = array(
                        'author_id' => $author_id,
                        'posts' => array()
                    );

                    foreach ($posts as $post) {
                        $response['posts'][] = array(
                            'title' => $post->post_title,
                            'date' => $post->post_date,
                            'id' => $post->ID
                        );
                    }
                } else {
                    $response = array(
                        'error' => 'No se encontraron entradas para este autor.'
                    );
                }
            } else {
                $response = array(
                    'error' => 'Usuario no encontrado.'
                );
            }
        } else {
            $response = array(
                'error' => 'Parámetro "author_id" no proporcionado en la URL.'
            );
        }

        header($HEADER_CTYPE);
        echo json_encode($response);
        exit;
    }
}
add_action('template_redirect', 'handlePostsByAuthorEndpoint');

// Maneja el endpoint personalizado para publicar un artículo
function handlePublishPostEndpoint() {
    $check_url = strpos($_SERVER['REQUEST_URI'], "publish-post") !== false;
    $check_title = isset($_POST['title']);
    $ckeck_content = isset($_POST['content']);
    $check_details = isset($_POST['author_id']) && isset($_POST['categories']);
    
    if ($check_url && $check_title && $ckeck_content && $check_details) {
        $title = sanitize_text_field($_POST['title']);
        $content = sanitize_text_field($_POST['content']);
        $author_id = intval($_POST['author_id']);
        $categories = $_POST['categories'];

        if ($title && $content && $author_id && $categories) {
            http_response_code(200);
            $post_id = wp_insert_post(array(
                'post_title' => $title,
                'post_content' => $content,
                'post_author' => $author_id,
                'post_status' => 'publish',
                'post_type' => 'post',
                'post_category' => array($categories)
            ));

            if ($post_id) {
                $response = array(
                    'post_id' => $post_id,
                    'message' => 'Artículo publicado exitosamente.'
                );
            } else {
                $response = array(
                    'error' => 'Error al publicar el artículo.'
                );
            }
        } else {
            $response = array(
                'error' => 'Parámetros faltantes en la solicitud.'
            );
        }

        header($HEADER_CTYPE);
        echo json_encode($response);
        exit;
    }
}
add_action('template_redirect', 'handlePublishPostEndpoint');

// Maneja el endpoint personalizado para obtener categorías
function handleCategoriesEndpoint() {
    if (strpos($_SERVER['REQUEST_URI'], "categories") !== false) {
        $categories = get_categories();

        if ($categories) {
            http_response_code(200);
            $response = array(
                'categories' => array()
            );

            foreach ($categories as $category) {
                $response['categories'][] = array(
                    'name' => $category->name,
                    'id' => $category->term_id
                );
            }
        } else {
            $response = array(
                'error' => 'No se encontraron categorías.'
            );
        }

        header($HEADER_CTYPE);
        echo json_encode($response);
        exit;
    }
}
add_action('template_redirect', 'handleCategoriesEndpoint');
