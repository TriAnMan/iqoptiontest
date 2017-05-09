<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 08.05.2017
 * Time: 21:17
 */

namespace TriAn\IqoTest\core\exception\managed;


use TriAn\IqoTest\core\Message;

class AbsentUser extends ReportableException
{
    /**
     * @var int[]
     */
    protected $users = [];

    /**
     * @param int[] $users
     */
    public function __construct(array $users)
    {
        $this->users = $users;
        parent::__construct();
    }

    /**
     * @param int $user
     * @return AbsentUser
     */
    public function appendUser($user)
    {
        if (!in_array($user, $this->users)) {
           $this->users[] = $user;
        }
        return $this;
    }

    /**
     * @return int[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param Message $request
     * @return \stdClass response
     */
    public function generateResponse(Message $request)
    {
        $response = $request->getBody();
        $response->absent_users = $this->users;
        $response->error = 'absent_users';
        return $response;
    }
}