<?php
function estaLogueado(){ return isset($_SESSION['usuario_id']); }
function esAdmin(){ return ($_SESSION['rol'] ?? '') === 'admin'; }
function esVeterinario(){ return ($_SESSION['rol'] ?? '') === 'veterinario'; }
function e($v){ return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }
function carritoCount(){ return array_sum($_SESSION['carrito'] ?? []); }
