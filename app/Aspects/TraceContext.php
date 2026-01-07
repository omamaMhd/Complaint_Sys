<?php
namespace App\Aspects;

class TraceContext
{
    protected static ?string $entity = null;
    protected static ?int $entityId = null;
    protected static ?string $traceId = null;
    protected static ?int $userId = null;
    protected static ?string $userRole = null;
    // public static function setUser(int $id, string $role): void
    // {
    //     self::$userId = $id;
    //     self::$userRole = $role;
    // }
     public static function setActor(int $userId, string $role): void
    {
        self::$userId = $userId;
        self::$userRole = $role;
    }

    public static function getActor(): array
    {
        return [
            'user_id' => self::$userId,
            'user_role' => self::$userRole,
        ];
    }
    public static function setEntity(string $entity, int $entityId): void
    {
        self::$entity = $entity;
        self::$entityId = $entityId;
    }

    // public static function getUserId(): ?int
    // {
    //     return self::$userId;
    // }

    // public static function getUserRole(): ?string
    // {
    //     return self::$userRole;
    // }


    public static function getEntity(): ?string
    {
        return self::$entity;
    }

    public static function getEntityId(): ?int
    {
        return self::$entityId;
    }

    public static function setTraceId(string $traceId): void
    {
        self::$traceId = $traceId;
    }

    public static function getTraceId(): ?string
    {
        return self::$traceId;
    }

    public static function clear(): void
    {
        self::$entity = null;
        self::$entityId = null;
        self::$traceId = null;
         self::$userId = null;
        self::$userRole = null;
    }
}
