<?php
/*
 * Copyright (C) 2009 Igalia, S.L. <info@igalia.com>
 *
 * This file is part of PhpReport.
 *
 * PhpReport is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PhpReport is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PhpReport.  If not, see <http://www.gnu.org/licenses/>.
 */


/* We check authentication and authorization */
require_once('phpreport/web/auth.php');

$user = $_SESSION['user'];

/* Include the generic header and sidebar*/
define(PAGE_TITLE, "PhpReport - Tasks");
include("include/header.php");
include("include/sidebar.php");

/* Get the needed variables to be passed to the Javascript code */
if(isset($_GET["date"]))
    $date = $_GET["date"];
else
    $date = date("Y-m-d");

?>
<script type="text/javascript">

function updateTimes(field, min, max) {
    if(min == null){
        min = field.parseDate('00:00');
    }
    if(max == null){
        max = field.parseDate('23:59');
    }
    var times = [];
    while(min <= max){
        times.push([min.dateFormat(field.format)]);
        min = min.add('mi', field.increment);
    }
    field.store.loadData(times);
};

/* Global variables extracted from the PHP side */
var date = '<?php echo $date?>';
var user = '<?php echo $user->getLogin()?>';

var App = new Ext.App({});

/* Schema of the information about tasks */
var taskRecord = new Ext.data.Record.create([
    {name:'id'},
    {name:'date'},
    {name:'initTime'},
    {name:'endTime'},
    {name:'story'},
    {name:'telework'},
    {name:'ttype'},
    {name:'text'},
    {name:'phase'},
    {name:'userId'},
    {name:'projectId'},
    {name:'customerId'},
    {name:'taskStoryId'}
]);

/* Schema of the information about customers */
var customerRecord = new Ext.data.Record.create([
    {name:'id'},
    {name:'name'},
]);
/* Schema of the information about projects */
var projectRecord = new Ext.data.Record.create([
    {name:'id'},
    {name:'description'},
]);
/* Schema of the information about task-stories */
var taskStoryRecord = new Ext.data.Record.create([
    {name:'id'},
    {name:'friendlyName'},
]);

/*  Class that stores a taskRecord element and shows it on screen.
    It keeps the taskRecord in synch with the content of the form on screen,
    in real-time (as soon as it changes). */
var TaskPanel = Ext.extend(Ext.Panel, {
    initComponent: function() {

        Ext.apply(this, {
            /* Preconfigured options */
            frame: true,
            title: 'Task',
            monitorResize: true,
            collapsible: true,
            layout:'column',

            /* Inputs of the task form */
            initTimeField: new Ext.form.TimeField({
	        parent: this,
                allowBlank: false,
                width: 60,
                format: 'H:i',
                minValue: '0:00',
                maxValue: '23:59',
                increment: 15,
                initTimeField: true,
                vtype: 'timerange',
                listeners: {
                    'change': function() {
                        this.parent.taskRecord.set('initTime',this.getValue());
                    }
                },
            }),
            endTimeField: new Ext.form.TimeField({
                parent: this,
                allowBlank: false,
                width: 60,
                format: 'H:i',
                minValue: '0:00',
                maxValue: '23:59',
                increment: 15,
                endTimeField: true,
                vtype: 'timerange',
                listeners: {
                    'change': function() {
                        this.parent.taskRecord.set('endTime',this.getValue());
                    }
                },
            }),
            taskTypeField: new Ext.form.Field({
                parent: this,
                value: this.taskRecord.data['ttype'],
                listeners: {
                    'change': function() {
                        this.parent.taskRecord.set('ttype',this.getValue());
                    }
	        }
            }),
            storyField: new Ext.form.Field({
                parent: this,
                value: this.taskRecord.data['story'],
                listeners: {
                    'change': function() {
                        this.parent.taskRecord.set('story',this.getValue());
                    }
                }
            }),
            descriptionTextArea: new Ext.form.TextArea({
                parent: this,
                height:205,
                value: this.taskRecord.data['text'],
                listeners: {
                    'change': function() {
                        this.parent.taskRecord.set('text',this.getValue());
                    }
                }
            }),
            teleworkCheckBox: new Ext.form.Checkbox({
                parent: this,
                value: (this.taskRecord.data['telework']=='true')?true:false,
                listeners: {
                    'change': function() {
                        this.parent.taskRecord.set('telework',this.getValue());
                    }
                }
            }),
            deleteButton: new Ext.Button({
                parent: this,
                text:'Delete',
                width: 60,
                margins: "7px 0 0 85px",
                handler: function() {
                    // We remove the TaskRecord from the Store, the TaskPanel
                    // from the parent panel and reload it
                    this.parent.store.remove(this.parent.taskRecord);
                    this.parent.parent.remove(this.parent);
                    this.parent.parent.doLayout();
                }
            }),
            customerComboBox: new Ext.form.ComboBox({
                parent: this,
                store: new Ext.data.Store({
                    parent: this,
                    autoLoad: true,  //initial data are loaded in the application init
                    autoSave: false, //if set true, changes will be sent instantly
                    baseParams: {
                        'login': user,
                        'active': 'true',
                    },
                    proxy: new Ext.data.HttpProxy({url: 'services/getUserCustomersService.php', method: 'GET'}),
                    reader:new Ext.data.XmlReader({record: 'customer', id:'id' }, customerRecord),
                    remoteSort: false,
                    listeners: {
                        'load': function () {
                            //the value of customerComboBox has to be set after loading the data on this store
                            this.parent.customerComboBox.setValue(this.parent.taskRecord.data['customerId']);
                        }
                    },
                }),
                mode: 'local',
                typeAhead: true,
                valueField: 'id',
                displayField: 'name',
                triggerAction: 'all',
                listeners: {
                    'change': function() {
                        this.parent.taskRecord.set('customerId',this.getValue());

                        //invoke changes in the "Projects" combo box
                        this.parent.projectComboBox.store.setBaseParam('cid',this.parent.taskRecord.data['customerId']);
                        this.parent.projectComboBox.store.load();
                    }
                },
            }),
            projectComboBox: new Ext.form.ComboBox({
                parent: this,
                store: new Ext.data.Store({
                    parent: this,
                    autoLoad: true,  //initial data are loaded in the application init
                    autoSave: false, //if set true, changes will be sent instantly
                    baseParams: {
                        'login': user,
                        'cid': this.taskRecord.data['customerId'],
                    },
                    proxy: new Ext.data.HttpProxy({url: 'services/getCustomerProjectsService.php', method: 'GET'}),
                    reader:new Ext.data.XmlReader({record: 'project', id:'id' }, projectRecord),
                    remoteSort: false,
                    listeners: {
                        'load': function () {
                            //the value of projectComboBox has to be set after loading the data on this store
                            this.parent.projectComboBox.setValue(this.parent.taskRecord.data['projectId']);
                        }
                    },
                }),
                mode: 'local',
                valueField: 'id',
                typeAhead: true,
                triggerAction: 'all',
                displayField: 'description',
                valueNotFoundText: '',
                listeners: {
                    'change': function() {
                        this.parent.taskRecord.set('projectId',this.getValue());

                        //invoke changes in the "Projects" combo box
                        this.parent.taskStoryComboBox.store.setBaseParam('pid',this.parent.taskRecord.data['projectId']);
                        this.parent.taskStoryComboBox.store.load();
                    },
                }
            }),
            taskStoryComboBox: new Ext.form.ComboBox({
                parent: this,
                store: new Ext.data.Store({
                    parent: this,
                    autoLoad: true,  //initial data are loaded in the application init
                    autoSave: false, //if set true, changes will be sent instantly
                    baseParams: {
                        'uidActive': true,
                        'pid': this.taskRecord.data['projectId'],
                    },
                    proxy: new Ext.data.HttpProxy({url: 'services/getOpenTaskStoriesService.php', method: 'GET'}),
                    reader:new Ext.data.XmlReader({record: 'taskStory', id:'id' }, taskStoryRecord),
                    remoteSort: false,
                    listeners: {
                        'load': function () {
                            //the value of projectComboBox has to be set after loading the data on this store
                            this.parent.taskStoryComboBox.setValue(this.parent.taskRecord.data['taskStoryId']);
                        }
                    },
                }),
                mode: 'local',
                valueField: 'id',
                typeAhead: true,
                triggerAction: 'all',
                displayField: 'friendlyName',
                valueNotFoundText: '',
                listeners: {
                    'change': function() {
                        this.parent.taskRecord.set('taskStoryId',this.getValue());
                    },
                }
            }),
        });

        /* Set the value of the checkbox correctly */
        this.teleworkCheckBox.setValue((this.taskRecord.data['telework']=='true')?true:false);

        /* Place the subelements correctly into the form */
        leftBox = new Ext.Panel({
            layout: 'anchor',
            width: 220,
            defaults: {width: 215},
            items: [
                new Ext.Container({
                    layout: 'hbox',
                    layoutConfig: {defaultMargins: "0 5px 0 0"},
                    items:[
                        new Ext.form.Label({text: 'Time '}),
                        this.initTimeField,
                        new Ext.form.Label({text: ' - '}),
                        this.endTimeField,
                    ]
                }),
                new Ext.form.Label({text: 'Customer'}),
                this.customerComboBox,
                new Ext.form.Label({text: 'Project'}),
                this.projectComboBox,
                new Ext.form.Label({text: 'Task type'}),
                this.taskTypeField,
                new Ext.form.Label({text: 'Story'}),
                this.storyField,
                new Ext.form.Label({text: 'TaskStory'}),
                this.taskStoryComboBox,
                new Ext.Container({
                    layout: 'hbox',
                    layoutConfig: {defaultMargins: "7px 5px 0 0"},
                    items:[
                        new Ext.form.Label({text: 'Telework'}),
                        this.teleworkCheckBox,
                        this.deleteButton
                    ]
                })
            ],
        });
        rightBox = new Ext.Panel({
            layout:'fit',
            monitorResize: true,
            columnWidth: 1,
            items:[
                this.descriptionTextArea,
            ],
        });
        this.items = [leftBox, rightBox];

        /* call the superclass to preserve base class functionality */
        TaskPanel.superclass.initComponent.apply(this, arguments);
    }
});


Ext.onReady(function(){

    Ext.QuickTips.init();

    /* Container for the TaskPanels (with scroll bars enabled) */
    var tasksScrollArea = new Ext.Container({autoScroll:true,  renderTo: 'tasks'});

    /* Proxy to the services related with load/save tasks */
    var myProxy = new Ext.data.HttpProxy({
    method: 'POST',
        api: {
            read    : {url: 'services/getUserTasksService.php', method: 'GET'},
            create    : 'services/createTasksService.php',
            update  : 'services/updateTasksService.php',
            destroy : 'services/deleteTasksService.php'
        },
    });
    /* Store to load/save tasks */
    var myStore = new Ext.data.Store({
        autoLoad: true,  //initial data are loaded in the application init
        autoSave: false, //if set true, changes will be sent instantly
        baseParams: {
            'login': user,
            'date': date,
            'dateFormat': 'Y-m-d',
        },
        storeId: 'id',
        proxy: myProxy,
        reader:new Ext.data.XmlReader({record: 'task', idProperty:'id' }, taskRecord),
        writer:new Ext.data.XmlWriter({encode: true, writeAllFields: false, root: 'tasks', tpl:'<tpl for="."><' + '?xml version="{version}" encoding="{encoding}"?' + '><tpl if="records.length&gt;0"><tpl if="root"><{root}><tpl for="records"><tpl if="fields.length&gt;0"><{parent.record}><date>' + date  + '</date><tpl for="fields"><{name}>{value}</{name}></tpl></{parent.record}></tpl></tpl></{root}></tpl></tpl></tpl>'}, taskRecord),
        remoteSort: false,
        listeners: {
            'load': function () {
                this.each(function(r) {
                    taskPanel = new TaskPanel({parent: tasksScrollArea, store: myStore, taskRecord:r});
                    tasksScrollArea.add(taskPanel);
                    taskPanel.doLayout();
                    tasksScrollArea.doLayout();

                    // We set the time values as raw ones, just for avoiding
                    // infinite validations
                    taskPanel.initTimeField.setRawValue(r.data['initTime']);
                    taskPanel.initTimeField.validate();
                    taskPanel.endTimeField.setRawValue(r.data['endTime']);
                    taskPanel.endTimeField.validate();
                })
            },
            'write': function() {
                App.setAlert(true, "Task Records Changes Saved");
            },
            'exception': function(){
                App.setAlert(false, "Some Error Occurred While Saving The Changes (please check you haven't clipped working hours)");
            }
        }
    });

    /* Add a callback to add new tasks */
    Ext.get('newTask').on('click', function(){
        newTask = new taskRecord();
        myStore.add(newTask);
        taskPanel = new TaskPanel({parent: tasksScrollArea, taskRecord:newTask, store: myStore});
        tasksScrollArea.add(taskPanel);
        taskPanel.doLayout();
        tasksScrollArea.doLayout();

        // We set the current time as end, and do focus on it in order to
        // save the field as 'changed'
        var now = new Date();
        taskPanel.endTimeField.setRawValue(now.format('H:i'));
        taskPanel.endTimeField.validate();
        taskPanel.endTimeField.focus();
        taskPanel.initTimeField.focus();
    });

    /* Add a callback to save tasks */
    Ext.get('save').on('click', function(){

        // First we check if the time fields of all records are valid
        var panels = tasksScrollArea.items;
        var valids = true;
        for(var panel=0; panel<panels.getCount(); panel++) {
            if (!panels.get(panel).initTimeField.isValid() || !panels.get(panel).endTimeField.isValid()) {
                valids = false;
                break;
            }
        }

        // If they are so, then we save the changes
        if (valids)
            myStore.save();
        else  // Otherwise, we print the error message
          App.setAlert(false, "Check For Invalid Field Values");
    });

    /* Build a calendar on the auxiliar sidebar */
    new Ext.Panel({
        renderTo: Ext.get("auxiliarpanel"),
        items: [{
            xtype: 'datepicker',
            value: Date.parseDate(date, 'd-m-Y'),
            listeners: {'select': function (item, date) {
                window.location = "tasks.php?date=" + date.format('Y-m-d');
            }}
        }],
    });
});
</script>

<div id="auxiliarpanel">
</div>

<div id="content">
    <div id="tasks"></div>
    <input type="submit" id="newTask" value="New Task">
    <input type="submit" id="save" value="Save">
</div>

<?php
/* Include the footer to close the header */
include("include/footer.php");
?>
