<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * ActivityLogs Controller
 *
 * @property \App\Model\Table\ActivityLogsTable $ActivityLogs
 */
class ActivityLogsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->ActivityLogs->find()
            ->contain(['Users']);
        $activityLogs = $this->paginate($query);

        $this->set(compact('activityLogs'));
    }

    /**
     * View method
     *
     * @param string|null $id Activity Log id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $activityLog = $this->ActivityLogs->get($id, contain: ['Users']);
        $this->set(compact('activityLog'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $activityLog = $this->ActivityLogs->newEmptyEntity();
        if ($this->request->is('post')) {
            $activityLog = $this->ActivityLogs->patchEntity($activityLog, $this->request->getData());
            if ($this->ActivityLogs->save($activityLog)) {
                $this->Flash->success(__('The activity log has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The activity log could not be saved. Please, try again.'));
        }
        $users = $this->ActivityLogs->Users->find('list', limit: 200)->all();
        $this->set(compact('activityLog', 'users'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Activity Log id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $activityLog = $this->ActivityLogs->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $activityLog = $this->ActivityLogs->patchEntity($activityLog, $this->request->getData());
            if ($this->ActivityLogs->save($activityLog)) {
                $this->Flash->success(__('The activity log has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The activity log could not be saved. Please, try again.'));
        }
        $users = $this->ActivityLogs->Users->find('list', limit: 200)->all();
        $this->set(compact('activityLog', 'users'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Activity Log id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $activityLog = $this->ActivityLogs->get($id);
        if ($this->ActivityLogs->delete($activityLog)) {
            $this->Flash->success(__('The activity log has been deleted.'));
        } else {
            $this->Flash->error(__('The activity log could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
