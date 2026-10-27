<?php

if (isActionAccessible($guid, $connection2, '/modules/Graficas/familias.php') == false) {
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    $page->breadcrumbs->add('Graficas familias');
}
