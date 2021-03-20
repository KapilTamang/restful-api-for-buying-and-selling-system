<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Models\User;

class UserTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [
        //
    ];
    
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        //
    ];
    
    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(User $user)
    {
        return [
            'indentifier' => (int)$user->id,
            'name' => (string)$user->name,
            'email' => (string)$user->email,
            'isVerified' => isset($user->email_verified_at) ? (string)$user->email_verified_at : null,
            'isVerified' => isset($usepasswordail_verified_at) ? (string)$user->email_verified_at : null,
            'isAdmin' => ($user->admin),
            'creationDate' => (string)$user->created_at,
            'updateDate' => (string)$user->updated_at,
            'deleteDate' => isset($user->deleted_at) ? (string)$user->deleted_at : null,
            'links' => [
                'rel' => 'self',
                'href' => route('users.show', $user->id),
            ]
        ];
    }


    public static function originalAttribute($index)
    {
        $attributes = [
            'identifier' => 'id',
            'name' => 'name', 
            'email' => 'email',
            'isVerified' => 'email_verified_at',
            'isAdmin' => 'admin',
            'creationDate' => 'created_at',
            'updateDate' => 'updated_at',
            'deleteDate' => 'deleted_at'
        ];

        return isset($attributes[$index]) ? $attributes[$index] : $index;
    }

    
    public static function transformedAttribute($index)
    {
        $attributes = [
            'id' => 'identifier',
            'name' => 'name',
            'email' => 'email',
            'password' => 'password',
            'email_verified_at' => 'isVerified',
            'admin' => 'isAdmin',
            'created_at' => 'creationDate',
            'updated_at' => 'updateDate',
            'deleted_at' => 'deleteDate',
        ];

        return isset($attributes[$index]) ? $attributes[$index] : null;
    }
}
