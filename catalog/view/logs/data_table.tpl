<div class="row col-lg-12">
    <div class="col-lg-7">
        <div class="panel panel-default">
            <div class="panel-heading">
                <a href="#" title="cron logs"><i class="fa fa-shell fa-fw"></i> Cron Logs</a>
                <a href="#event_logs_panel" title="event logs" class="pull-right" style="margin-left: 10px">Event logs</a>
            </div>
            <!-- /.panel-heading -->
            <div class="panel-body">
                <div class="dataTable_wrapper">
                    <table class="table table-striped table-bordered table-hover" id="dataTables-cronlogs">
                        <thead>
                            <tr>
                                <th>time</th>
                                <th>log</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($cron_logs as $log) { ?>
                            <tr class="odd gradeX each_inventory_item">
                                <td style="width: 30%"><?php echo "[{$log['time']}] "; ?></td>
                                <td><?php echo $log['log']; ?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <!--
                <div class="list-group">
                    <?php foreach($cron_logs as $log) { ?>
                        <a href="#" class="list-group-item">
                            <i class=""></i> <?php echo $log['text']; ?>
                            <span class="pull-right text-muted small"><em><?php //echo $log['diff_time']; ?></em>
                            </span>
                        </a>
                    <?php } ?>
                </div> -->
                <!-- /.list-group -->
            </div>
            <!-- /.panel-body -->
        </div>
        <!-- /.panel -->
    </div>

    <div class="col-lg-5">
        <div class="panel panel-default" id="event_logs_panel">
            <div class="panel-heading">
                <i class="fa fa-history fa-fw"></i> event Logs
            </div>
            <!-- /.panel-heading -->
            <div class="panel-body">
                <div class="dataTable_wrapper">
                    <table class="table table-striped table-bordered table-hover" id="dataTables-evlogs">
                        <thead>
                            <tr>
                                <th>time</th>
                                <th>log</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($event_logs as $log) { ?>
                            <tr class="odd gradeX each_inventory_item">
                                <td style="width: 30%"><?php echo "[{$log['time']}] "; ?></td>
                                <td><?php echo $log['log']; ?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <!--
                    <div class="list-group">
                        <?php foreach($event_logs as $log) { ?>
                            <a href="#" class="list-group-item">
                                <i class=""></i> <?php echo '['.$log['log_time'].' '.$log['timezone'].'] '.$log['text']; ?>
                                <span class="pull-right text-muted small"><em><?php //echo $log['diff_time']; ?></em>
                                </span>
                            </a>
                        <?php } ?>
                    </div> -->
                <!-- /.list-group -->
            </div>
            <!-- /.panel-body -->
        </div>
        <!-- /.panel -->
    </div>
    <!-- /.col-lg-8 -->

</div>
<!-- /.row -->