<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */
use App\Model\Entity\Role;

$lang = $this->request->getParam('lang', 'en');
$identity = $this->request->getAttribute('identity');
$isLoggedIn = $identity !== null;
$isAdmin = $isLoggedIn && (int)$identity->get('role_id') === Role::ADMIN;
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit User'), ['action' => 'edit', $user->id, 'lang' => $lang], ['class' => 'side-nav-item']) ?>
            <?php if ($isAdmin) : ?>
                <?= $this->Form->postLink(__('Delete User'), ['action' => 'delete', $user->id, 'lang' => $lang], ['confirm' => __('Are you sure you want to delete # {0}?', $user->id), 'class' => 'side-nav-item']) ?>
                <?= $this->Html->link(__('List Users'), ['action' => 'index', 'lang' => $lang], ['class' => 'side-nav-item']) ?>
                <?= $this->Html->link(__('New User'), ['action' => 'add', 'lang' => $lang], ['class' => 'side-nav-item']) ?>
            <?php endif; ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="users view content">
            <h3><?= h($user->email) ?></h3>
            <table>
                <tr>
                    <th><?= __('Email') ?></th>
                    <td><?= h($user->email) ?></td>
                </tr>
                <?php if ($isAdmin) : ?>
                <tr>
                    <th><?= __('Password Hash') ?></th>
                    <td><?= h($user->password_hash) ?></td>
                </tr>
                <tr>
                    <th><?= __('Role') ?></th>
                    <td><?= $user->hasValue('role') ? $this->Html->link($user->role->name, ['controller' => 'Roles', 'action' => 'view', $user->role->id, 'lang' => $lang]) : '' ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th><?= __('Avatar Url') ?></th>
                    <td>
                        <?php if ($user->avatar_url) : ?>
                            <?= $this->Html->image($user->avatar_url, ['alt' => __('Avatar'), 'style' => 'max-width: 100px; max-height: 100px;']) ?>
                        <?php else : ?>
                            <?= h($user->avatar_url) ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?= __('Last Login At') ?></th>
                    <td><?= h($user->last_login_at) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created At') ?></th>
                    <td><?= h($user->created_at) ?></td>
                </tr>
                <?php if ($isAdmin) : ?>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($user->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Updated At') ?></th>
                    <td><?= h($user->updated_at) ?></td>
                </tr>
                <tr>
                    <th><?= __('Is Active') ?></th>
                    <td><?= $user->is_active ? __('Yes') : __('No'); ?></td>
                </tr>
                <tr>
                    <th><?= __('Is Blocked') ?></th>
                    <td><?= $user->is_blocked ? __('Yes') : __('No'); ?></td>
                </tr>
                <?php endif; ?>
            </table>
            <?php if ($isAdmin) : ?>
            <div class="related">
                <h4><?= __('Related Activity Logs') ?></h4>
                <?php if (!empty($user->activity_logs)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('User Id') ?></th>
                            <th><?= __('Action') ?></th>
                            <th><?= __('Entity Type') ?></th>
                            <th><?= __('Entity Id') ?></th>
                            <th><?= __('Ip Address') ?></th>
                            <th><?= __('User Agent') ?></th>
                            <th><?= __('Created At') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($user->activity_logs as $activityLog) : ?>
                        <tr>
                            <td><?= h($activityLog->id) ?></td>
                            <td><?= h($activityLog->user_id) ?></td>
                            <td><?= h($activityLog->action) ?></td>
                            <td><?= h($activityLog->entity_type) ?></td>
                            <td><?= h($activityLog->entity_id) ?></td>
                            <td><?= h($activityLog->ip_address) ?></td>
                            <td><?= h($activityLog->user_agent) ?></td>
                            <td><?= h($activityLog->created_at) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'ActivityLogs', 'action' => 'view', $activityLog->id, 'lang' => $lang]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'ActivityLogs', 'action' => 'edit', $activityLog->id, 'lang' => $lang]) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['controller' => 'ActivityLogs', 'action' => 'delete', $activityLog->id, 'lang' => $lang],
                                    [
                                        'method' => 'delete',
                                        'confirm' => __('Are you sure you want to delete # {0}?', $activityLog->id),
                                    ]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Ai Requests') ?></h4>
                <?php if (!empty($user->ai_requests)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('User Id') ?></th>
                            <th><?= __('Test Id') ?></th>
                            <th><?= __('Language Id') ?></th>
                            <th><?= __('Type') ?></th>
                            <th><?= __('Input Payload') ?></th>
                            <th><?= __('Output Payload') ?></th>
                            <th><?= __('Status') ?></th>
                            <th><?= __('Created At') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($user->ai_requests as $aiRequest) : ?>
                        <tr>
                            <td><?= h($aiRequest->id) ?></td>
                            <td><?= h($aiRequest->user_id) ?></td>
                            <td><?= h($aiRequest->test_id) ?></td>
                            <td><?= h($aiRequest->language_id) ?></td>
                            <td><?= h($aiRequest->type) ?></td>
                            <td><?= h($aiRequest->input_payload) ?></td>
                            <td><?= h($aiRequest->output_payload) ?></td>
                            <td><?= h($aiRequest->status) ?></td>
                            <td><?= h($aiRequest->created_at) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['prefix' => 'Admin', 'controller' => 'AiRequests', 'action' => 'view', $aiRequest->id, 'lang' => $lang]) ?>
                                <?= $this->Html->link(__('Edit'), ['prefix' => 'Admin', 'controller' => 'AiRequests', 'action' => 'edit', $aiRequest->id, 'lang' => $lang]) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['prefix' => 'Admin', 'controller' => 'AiRequests', 'action' => 'delete', $aiRequest->id, 'lang' => $lang],
                                    [
                                        'method' => 'delete',
                                        'confirm' => __('Are you sure you want to delete # {0}?', $aiRequest->id),
                                    ]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Device Logs') ?></h4>
                <?php if (!empty($user->device_logs)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('User Id') ?></th>
                            <th><?= __('Ip Address') ?></th>
                            <th><?= __('User Agent') ?></th>
                            <th><?= __('Device Type') ?></th>
                            <th><?= __('Country') ?></th>
                            <th><?= __('City') ?></th>
                            <th><?= __('Created At') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php 
                        $deviceTypeLabels = [
                            0 => __('Mobile'),
                            1 => __('Tablet'),
                            2 => __('Desktop'),
                        ];
                        ?>
                        <?php foreach ($user->device_logs as $deviceLog) : ?>
                        <tr>
                            <td><?= h($deviceLog->id) ?></td>
                            <td><?= h($deviceLog->user_id) ?></td>
                            <td><?= h($deviceLog->ip_address) ?></td>
                            <td><?= h($deviceLog->user_agent) ?></td>
                            <td><?= h($deviceTypeLabels[$deviceLog->device_type] ?? __('Unknown')) ?></td>
                            <td><?= h($deviceLog->country) ?></td>
                            <td><?= h($deviceLog->city) ?></td>
                            <td><?= h($deviceLog->created_at) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['prefix' => 'Admin', 'controller' => 'DeviceLogs', 'action' => 'view', $deviceLog->id, 'lang' => $lang]) ?>
                                <?= $this->Html->link(__('Edit'), ['prefix' => 'Admin', 'controller' => 'DeviceLogs', 'action' => 'edit', $deviceLog->id, 'lang' => $lang]) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['prefix' => 'Admin', 'controller' => 'DeviceLogs', 'action' => 'delete', $deviceLog->id, 'lang' => $lang],
                                    [
                                        'method' => 'delete',
                                        'confirm' => __('Are you sure you want to delete # {0}?', $deviceLog->id),
                                    ]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Test Attempts') ?></h4>
                <?php if (!empty($user->test_attempts)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('User Id') ?></th>
                            <th><?= __('Test Id') ?></th>
                            <th><?= __('Category Id') ?></th>
                            <th><?= __('Difficulty Id') ?></th>
                            <th><?= __('Language Id') ?></th>
                            <th><?= __('Started At') ?></th>
                            <th><?= __('Finished At') ?></th>
                            <th><?= __('Score') ?></th>
                            <th><?= __('Total Questions') ?></th>
                            <th><?= __('Correct Answers') ?></th>
                            <th><?= __('Created At') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($user->test_attempts as $testAttempt) : ?>
                        <tr>
                            <td><?= h($testAttempt->id) ?></td>
                            <td><?= h($testAttempt->user_id) ?></td>
                            <td><?= h($testAttempt->test_id) ?></td>
                            <td><?= h($testAttempt->category_id) ?></td>
                            <td><?= h($testAttempt->difficulty_id) ?></td>
                            <td><?= h($testAttempt->language_id) ?></td>
                            <td><?= h($testAttempt->started_at) ?></td>
                            <td><?= h($testAttempt->finished_at) ?></td>
                            <td><?= h($testAttempt->score) ?></td>
                            <td><?= h($testAttempt->total_questions) ?></td>
                            <td><?= h($testAttempt->correct_answers) ?></td>
                            <td><?= h($testAttempt->created_at) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'TestAttempts', 'action' => 'view', $testAttempt->id, 'lang' => $lang]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'TestAttempts', 'action' => 'edit', $testAttempt->id, 'lang' => $lang]) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['controller' => 'TestAttempts', 'action' => 'delete', $testAttempt->id, 'lang' => $lang],
                                    [
                                        'method' => 'delete',
                                        'confirm' => __('Are you sure you want to delete # {0}?', $testAttempt->id),
                                    ]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related User Tokens') ?></h4>
                <?php if (!empty($user->user_tokens)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('User Id') ?></th>
                            <th><?= __('Token') ?></th>
                            <th><?= __('Type') ?></th>
                            <th><?= __('Expires At') ?></th>
                            <th><?= __('Used At') ?></th>
                            <th><?= __('Created At') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($user->user_tokens as $userToken) : ?>
                        <tr>
                            <td><?= h($userToken->id) ?></td>
                            <td><?= h($userToken->user_id) ?></td>
                            <td><?= h($userToken->token) ?></td>
                            <td><?= h($userToken->type) ?></td>
                            <td><?= h($userToken->expires_at) ?></td>
                            <td><?= h($userToken->used_at) ?></td>
                            <td><?= h($userToken->created_at) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'UserTokens', 'action' => 'view', $userToken->id, 'lang' => $lang]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'UserTokens', 'action' => 'edit', $userToken->id, 'lang' => $lang]) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['controller' => 'UserTokens', 'action' => 'delete', $userToken->id, 'lang' => $lang],
                                    [
                                        'method' => 'delete',
                                        'confirm' => __('Are you sure you want to delete # {0}?', $userToken->id),
                                    ]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; // End isAdmin check for related data ?>
        </div>
    </div>
</div>
