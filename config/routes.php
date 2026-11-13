<?php
/**
 * 用途：按 W02 保留公开路径与内部路由；控制器逐项由后续任务实现。
 * 作者：Clash727（W01）
 */

return [
    '' => 'site/index',
    'world-cup/matches' => 'world-cup/index',
    'world-cup/matches/<id:[1-9][0-9]*>' => 'world-cup/view',
    'team' => 'team/index',
    'team/members/<id:[1-9][0-9]*>' => 'team/member',
    'GET guestbook' => 'guestbook/index',
    'POST guestbook' => 'guestbook/create',
    // 无方法的规则让其他方法进入控制器的 VerbFilter，返回 405。
    'guestbook' => 'guestbook/index',
    'downloads' => 'download/index',
    'downloads/<category:team|personal>/<filename:[^/]+>' => 'download/file',
    'admin/login' => 'auth/login',
    'admin/logout' => 'auth/logout',
    'admin' => 'admin/index',
    'admin/team' => 'admin-team/update',
    'admin/members/<id:[1-9][0-9]*>/edit' => 'admin-member/update',
    'admin/matches' => 'admin-fixture/index',
    'admin/matches/create' => 'admin-fixture/create',
    'admin/matches/<id:[1-9][0-9]*>/edit' => 'admin-fixture/update',
    'admin/matches/<id:[1-9][0-9]*>/publish' => 'admin-fixture/publish',
    'admin/messages' => 'admin-guestbook/index',
    'admin/messages/<id:[1-9][0-9]*>/review' => 'admin-guestbook/review',
];
