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

namespace Fisharebest\Webtrees\Http\Middleware;

use Fisharebest\Webtrees\Module\ModuleThemeInterface;
use Fisharebest\Webtrees\Module\WebtreesTheme;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Session;
use Fisharebest\Webtrees\Site;
use Fisharebest\Webtrees\Tree;
use Generator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware to set a global theme.
 */
class UseTheme implements MiddlewareInterface
{
    /** @var ModuleService */
    private $module_service;

    /**
     * UseTheme constructor.
     *
     * @param ModuleService $module_service
     */
    public function __construct(ModuleService $module_service)
    {
        $this->module_service = $module_service;
    }

    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        foreach ($this->themes($request) as $theme) {
            if ($theme instanceof ModuleThemeInterface) {
                // Bind this theme into the container
                app()->instance(ModuleThemeInterface::class, $theme);

                // Remember this setting
                Session::put('theme', $theme->name());

                break;
            }
        }

        return $handler->handle($request);
    }

    /**
     * The theme can be chosen in various ways.
     *
     * @param ServerRequestInterface $request
     *
     * @return Generator
     */
    private function themes(ServerRequestInterface $request): Generator
    {
        $themes = $this->module_service->findByInterface(ModuleThemeInterface::class);

        // Last theme used
        yield $themes->get(Session::get('theme', ''));

        // Default for tree
        $tree = $request->getAttribute('tree');

        if ($tree instanceof Tree) {
            yield $themes->get($tree->getPreference('THEME_DIR'));
        }

        // Default for site
        yield $themes->get(Site::getPreference('THEME_DIR'));

        // Default for application
        yield app(WebtreesTheme::class);
    }
}
