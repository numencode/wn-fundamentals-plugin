<?php

return [
    'plugin'    => [
        'name'        => 'Fundamentals',
        'description' => 'NumenCode Fundamentals za Winter CMS.',
    ],
    'field'     => [
        'id'                   => 'ID',
        'section'              => 'Sekcija',
        'title'                => 'Naslov',
        'subtitle'             => 'Podnaslov',
        'description'          => 'Opis',
        'content'              => 'Vsebina',
        'type'                 => 'Vrsta',
        'group'                => 'Skupina',
        'media'                => 'Media',
        'picture'              => 'Slika',
        'picture_main'         => 'Glavna slika',
        'picture_gallery'      => 'Galerija slik',
        'file'                 => 'Datoteka',
        'files'                => 'Datoteke',
        'link'                 => 'Povezava',
        'link_title'           => 'Besedilo povezave',
        'is_published'         => 'Objavljeno?',
        'is_published_comment' => 'Neobjavljene vnose vidijo samo administratorji.',
        'slug'                 => 'URL',
        'sort_order'           => 'Vrstni red',
    ],
    'timestamp' => [
        'created_at' => 'Ustvarjeno',
        'updated_at' => 'Posodobljeno',
        'deleted_at' => 'Izbrisano',
        'added_at'   => 'Dodano',
        'removed_at' => 'Odstranjeno',
    ],
    'item'      => [
        'title'  => 'Vnos',
        'items'  => 'Vnosi',
        'add'    => 'Dodaj nov vnos',
        'create' => 'Ustvari vnos',
        'delete' => 'Izbriši vnos',
        'update' => 'Uredi vnos',
        'count'  => 'Št. vnosov',
    ],
    'component' => [
        'layout_default' => 'Privzeto',
    ],
    'formwidget' => [
        'relationrepeater' => 'Ponavljalnik relacij',
    ],
    'repeater' => [
        'expand_all'   => 'Razširi vse',
        'collapse_all' => 'Strni vse',
    ],
    'relationable' => [
        'item_error' => 'Vnos :index: :message',
    ],
    'translate' => [
        'language'     => 'Jezik',
        'copy_from'    => 'Kopiraj iz…',
        'copy_confirm' => 'To bo prevedlo vsa polja za jezik :target iz jezika :source. Želite nadaljevati?',
    ],
];
