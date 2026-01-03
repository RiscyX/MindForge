<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * DeviceLogs Controller
 *
 * @property \App\Model\Table\DeviceLogsTable $DeviceLogs
 */
class DeviceLogsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->DeviceLogs->find()
            ->contain(['Users']);
        $deviceLogs = $this->paginate($query);

        $this->set(compact('deviceLogs'));
    }

    /**
     * View method
     *
     * @param string|null $id Device Log id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $deviceLog = $this->DeviceLogs->get($id, contain: ['Users']);
        $this->set(compact('deviceLog'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $deviceLog = $this->DeviceLogs->newEmptyEntity();
        if ($this->request->is('post')) {
            $deviceLog = $this->DeviceLogs->patchEntity($deviceLog, $this->request->getData());
            if ($this->DeviceLogs->save($deviceLog)) {
                $this->Flash->success(__('The device log has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The device log could not be saved. Please, try again.'));
        }
        $users = $this->DeviceLogs->Users->find('list', limit: 200)->all();
        $this->set(compact('deviceLog', 'users'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Device Log id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $deviceLog = $this->DeviceLogs->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $deviceLog = $this->DeviceLogs->patchEntity($deviceLog, $this->request->getData());
            if ($this->DeviceLogs->save($deviceLog)) {
                $this->Flash->success(__('The device log has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The device log could not be saved. Please, try again.'));
        }
        $users = $this->DeviceLogs->Users->find('list', limit: 200)->all();
        $this->set(compact('deviceLog', 'users'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Device Log id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $deviceLog = $this->DeviceLogs->get($id);
        if ($this->DeviceLogs->delete($deviceLog)) {
            $this->Flash->success(__('The device log has been deleted.'));
        } else {
            $this->Flash->error(__('The device log could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
