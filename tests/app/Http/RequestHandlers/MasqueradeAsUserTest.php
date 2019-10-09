<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2019 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Fisharebest\Webtrees\Http\RequestHandlers;

use Fig\Http\Message\RequestMethodInterface;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Services\UserService;
use Fisharebest\Webtrees\Session;
use Fisharebest\Webtrees\TestCase;
use Fisharebest\Webtrees\User;

/**
 * @covers \Fisharebest\Webtrees\Http\RequestHandlers\MasqueradeAsUser
 */
class MasqueradeAsUserTest extends TestCase
{
    /**
     * @return void
     */
    public function testMasqueradeAsUser(): void
    {
        $user1 = $this->createMock(User::class);
        $user1->method('id')->willReturn(1);

        $user2 = $this->createMock(User::class);
        $user2->method('id')->willReturn(2);

        $user_service = $this->createMock(UserService::class);
        $user_service->expects($this->once())->method('find')->willReturn($user2);

        $request = self::createRequest(RequestMethodInterface::METHOD_POST, [], ['user_id' => $user2->id()])
            ->withAttribute('user', $user1);

        $handler  = new MasqueradeAsUser($user_service);
        $response = $handler->handle($request);

        self::assertSame(self::STATUS_NO_CONTENT, $response->getStatusCode());
        self::assertSame($user2->id(), Auth::id());
        self::assertSame('1', Session::get('masquerade'));
    }

    /**
     * @return void
     */
    public function testCannotMasqueradeAsSelf(): void
    {
        $user = $this->createMock(User::class);
        $user->method('id')->willReturn(1);

        $user_service = $this->createMock(UserService::class);
        $user_service->expects($this->once())->method('find')->willReturn($user);

        $request = self::createRequest(RequestMethodInterface::METHOD_POST, [], ['user_id' => $user->id()])
            ->withAttribute('user', $user);

        $handler  = new MasqueradeAsUser($user_service);
        $response = $handler->handle($request);

        self::assertSame(self::STATUS_NO_CONTENT, $response->getStatusCode());
        self::assertNull(Session::get('masquerade'));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage User ID 2 not found
     * @return void
     */
    public function testMasqueradeAsNonExistingUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('id')->willReturn(1);

        $user_service = $this->createMock(UserService::class);
        $user_service->expects($this->once())->method('find')->willReturn(null);

        $request = self::createRequest(RequestMethodInterface::METHOD_POST, [], ['user_id' => 2])
            ->withAttribute('user', $user);

        $handler = new MasqueradeAsUser($user_service);
        $handler->handle($request);
    }
}
