<?php

return [
    'plugin'    => [
        'name'        => 'Fundamentals',
        'description' => 'NumenCode Fundamentals for Winter CMS.',
    ],
    'field'     => [
        'id'                   => 'ID',
        'section'              => 'Section',
        'title'                => 'Title',
        'subtitle'             => 'Subtitle',
        'description'          => 'Description',
        'content'              => 'Content',
        'type'                 => 'Type',
        'group'                => 'Group',
        'media'                => 'Media',
        'picture'              => 'Picture',
        'picture_main'         => 'Main picture',
        'picture_gallery'      => 'Picture gallery',
        'file'                 => 'File',
        'files'                => 'Files',
        'link'                 => 'Link',
        'link_title'           => 'Link title',
        'is_published'         => 'Published?',
        'is_published_comment' => 'Unpublished items can only be viewed by administrators.',
        'slug'                 => 'Slug',
        'sort_order'           => 'Sort order',
    ],
    'timestamp' => [
        'created_at' => 'Created at',
        'updated_at' => 'Updated at',
        'deleted_at' => 'Deleted at',
        'added_at'   => 'Added at',
        'removed_at' => 'Removed at',
    ],
    'item'      => [
        'title'  => 'Item',
        'items'  => 'Items',
        'add'    => 'Add new item',
        'create' => 'Create item',
        'delete' => 'Delete item',
        'update' => 'Update item',
        'count'  => 'No. of items',
    ],
    'component' => [
        'layout_default' => 'Default',
    ],
    'formwidget' => [
        'relationrepeater' => 'Relation repeater',
    ],
    'relationable' => [
        'item_error' => 'Item :index: :message',
    ],
    'translate' => [
        'language'     => 'Language',
        'copy_from'    => 'Copy from…',
        'copy_confirm' => 'This replaces the :target translations of all fields in this form with the :source values. Continue?',
    ],
];
