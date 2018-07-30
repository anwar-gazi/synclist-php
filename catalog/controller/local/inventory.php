<?php

/**
 * Class ControllerLocalInventory
 * @property SyncListApi $synclist_api
 * @property Modelinventoryinventory $model_inventory_inventory
 */
class ControllerLocalInventory extends Controller
{

    public function index()
    {
        $this->load->model('inventory/inventory');
        $data = [
            'items' => $this->model_inventory_inventory->items()
        ];
        $data['cronlog_errors'] = file_get_contents(join_path(DIR_LOGS, $this->registry->get('cron_error_log')));

        $this->response->setOutput($this->load->twig('inventory/inventory.twig', $data));
    }

}
