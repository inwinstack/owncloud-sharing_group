var $groupList,
    $GroupListLi,
	$sortGroupBy,
    $menuLink,
    appname = 'sharing_group';
    
var GroupList = {
	everyoneGID: '_everyone',
    groups: [],
    initgroup: $.Deferred(),
    group: $.Deferred(),
    groups_name: [],

    $_GET: function(param) {
        var vars = {};
        location.href.replace( location.hash, '' ).replace( 
            /[?&]+([^=&]+)=?([^&]*)?/gi, // regexp
            function( m, key, value ) { // callback
                vars[key] = value !== undefined ? value : '';
            }
        );

        if ( param ) {
            return vars[param] ? vars[param] : null;	
        }
        return vars;
    },

    elementBelongsToAddGroup: function(el) {
		return !(el !== $('#newgroup-form').get(0) &&
		$('#newgroup-form').find($(el)).length === 0);
	},

    hasAddGroupNameText: function() {
		var name = $('#newgroup-name').val();
		
        return $.trim(name) !== '';
	},
    
    showGroup: function (gid) {
		UserList.empty();
        var statereset = $('#checkuser').attr('checked') !== undefined || $('#checkuser').attr('indeterminate') !== undefined;
        
        if(statereset){
            $('#checkuser').tristate('state', false);
        }
        
        $groupList.find('li').removeClass('active');
        if (gid !== undefined && gid !== '') {
			GroupList.getGroupLI(gid).addClass('active');
            $('#checkuser').data({
                'user':$('#group-list').data(gid),
                'checkeduser':[]
            });
	        if (gid === '_everyone') {
                UserList.currentGid = gid; 
                
		        $userList.siblings('.loading').css('visibility', 'visible');
                UserList.update(gid);
            }
            else {
                UserList.currentGid = gid;
                UserList.update(gid);
            }
        }
	},
    
	isAddGroupButtonVisible: function() {
		return $('#newgroup-initial').is(":visible");
	},

    isGroupActionMenuVisible: function() {
        return $('.fileActionsMenu').is(":visible");;
    },

	toggleAddGroup: function(event) {
		if (GroupList.isAddGroupButtonVisible()) {
			event.stopPropagation();
			$('#newgroup-form').show();
			$('#newgroup-initial').hide();
			$('#newgroup-name').focus();
			GroupList.handleAddGroupInput('');
		}
		else {
			$('#newgroup-form').hide();
			$('#newgroup-initial').show();
			$('#newgroup-name').val('');
		}
	},

	handleAddGroupInput: function(input) {
		if(input.length) {
			$('#newgroup-form input[type="submit"]').attr('disabled', null);
		} else {
			$('#newgroup-form input[type="submit"]').attr('disabled', 'disabled');
		}
	},

	isGroupNameValid: function(groupname) {
		if ($.trim(groupname) === '') {
			OC.dialogs.alert(
				t(appname, 'A valid group name must be provided'),
				t(appname, 'Error creating group'));
			return false;
		}
		return true;
	},
      
    editGroup: function($element) {
		var oldname = $element.find('.group-name').text();
		var gid = $element.data('gid');
		var $editInput = $('<input type="text" />').val(oldname).attr({ id:'editInput'});
        var button = $('<button>').attr({
            class:'new-button primary icon-checkmark-white', 
            style:'display: block', 
            id:'rename-button'
        });
        var group_editing = $('<li>').attr({class:'group editing'});
        
        $element.hide();
        $editInput.insertBefore($element).wrap(group_editing);
        group_editing.append(button);
        $('.group.editing').append(button);
		var $editInput = $('<input type="text" />').val(oldname).attr({ id:'editInput'});
        var button = $('<button>').attr({
            class:'new-button primary icon-checkmark-white', 
            style:'display: block', 
            id:'rename-button'
        });
        var group_editing = $('<li>').attr({class:'group editing'});
        
        $element.hide();
        $editInput.insertBefore($element).wrap(group_editing);
        group_editing.append(button);
        $('.group.editing').append(button);
        var $tmpelem = $editInput.parent('li');
        $editInput.focus();

        $('#group-list').on('keyup', '#editInput', function(event) {
                if ($.inArray($editInput.val(), GroupList.groups_name) > -1) {
                    $editInput.addClass("ui-status-error");
                }
                else {
                    $editInput.removeClass("ui-status-error");
                }
                
                if (event.which == $.ui.keyCode.ESCAPE) {
                    $tmpelem.remove();
                    $element.show();
                }
                
                if (event.which == $.ui.keyCode.ENTER && !$editInput.hasClass('ui-status-error')) {
                    var newname = $editInput.val();
                    if (newname != '') {
                        GroupList.renameGroup($element , $tmpelem, gid, newname, oldname);
                    }
                }
        });
        
        $('#rename-button').click(function() {
            var newname = $editInput.val();
            if (newname != '' && !$editInput.hasClass('ui-status-error'))  {   
                GroupList.renameGroup($element, $tmpelem, gid, newname, oldname);
            }
        });
        
        $(document).on('click', function(event) {
            if (event.target.parentElement.className != 'group editing') {
                $tmpelem.remove();
                $element.show();
                $(document).off('click');
            }
        });
        
   	},
    
    renameGroup: function($element, $tmpelem , gid, newname, oldname) {
        $.post(
            OC.generateUrl('/apps/sharing_group/renameGroup'),
            {
                gid: gid,
                newname: newname
            },
            function (result) {
                $element.find('.group-name').text(newname);
                $tmpelem.remove();
                $element.show();
                GroupList.groups_name.splice(GroupList.groups_name.indexOf(oldname),1);
                GroupList.groups_name.push(newname);
                GroupList.sortGroups();
            });

    },

    getGroupLI: function(gid) {
		return $groupList.find('li.isgroup').filter(function() {
			return GroupList.getElementGID(this) === gid;
		});
	},
    
    getElementGID: function (element) {
		return ($(element).closest('li').data('gid') || '').toString();
	},

    addUserLi: function(gid, name){
        var li = $('<tr>').attr({
            'data-gid': GroupList.$_GET('gid'), 
            id: name, 
            class: 'isgroup'
        });
        var td = $('<td>').attr({class: 'groupname'});
        var group = $('<a>');
        var label = $('<label>').attr({for: 'select-group-4'});
        var icon_div = $('<div>').attr({class: 'usericon thumbnail'});

        var groupname = $('<span>').attr({class: 'user-list'});
        var util = $('<span>').attr({class: 'utils'});

        var action_menu = $('<span>').attr({class: 'deletebutton'});
        $menuLink = $('<a>').attr({
            class: 'action action-menu permanent',
            id: 'user_delete'
        });
        var menu_icon = $('<img>').attr({
            class: 'svg', 
            src: "../../../core/img/actions/delete.png"
        });
        
        $menuLink.append(menu_icon);
        action_menu.append($menuLink);

        group.append(groupname.text(name));
        group.append(action_menu);

        label.append(icon_div);
        td.append(label);
        td.append(group);
        li.append(td);

        return li;
    },

    addLi: function(gid, name, count, user){
        var li = $('<tr>').attr({
            'data-gid': gid , 
            id: name, 
            class: 'isgroup'
        });
        var td = $('<td>').attr({class: 'groupname'});
        var group = $('<a>');
        var label = $('<label>').attr({for: 'select-group-4'});
        var icon_div = $('<div>').attr({class: 'groupicon thumbnail'});

        var groupname = $('<span>').attr({class: 'group-name'});
        var util = $('<span>').attr({class: 'utils'});
        var usercount = $('<span>').attr({class: 'user-count'});
        var action_rename = $('<a>').attr({class: 'icon-rename action rename'});

        var action_menu = $('<span>').attr({class: 'menuactions'});
        $menuLink = $('<a>').attr({
            class: 'action action-menu permanent',
            id: 'action_menu'
        });
        var menu_icon = $('<img>').attr({
            class: 'svg', 
            src: "../../../core/img/actions/more.png"
        });
        
        if (user != null){
            user = user.split(',', count);
            $('#group-list').data(gid, user);
        }
        else {
            $('#group-list').data(gid, []);
        }
        $menuLink.append(menu_icon);
        action_menu.append($menuLink);

        group.append(groupname.text(name));
        group.append(action_menu);

        label.append(icon_div);
        td.append(label);
        td.append(group);
        li.append(td);

        return li;
    },
    
    showGroupList: function() {
		$.get(
			OC.generateUrl('/apps/sharing_group/getCreatedGroups'),
			function(result) {
                 $('.loading').remove();
                 $.each(result.data, function(index, group) {
                    GroupList.groups.push(group.gid);
                    GroupList.groups_name.push(index);

                    $GroupListLi.after(GroupList.addLi(group.gid, index, group.count, group.user));
                    GroupList.sortGroups();
                    GroupList.initgroup.resolve(result);
                });
			}
		);
	},

    showGroupUsers: function(gid) {
        $.get(
			OC.generateUrl('/apps/sharing_group/getUsersInGroup'),
            {
                gid: gid
            },
			function(result) {
                 $('.loading').remove();
                 $.each(result.data, function(index, group) {
                    GroupList.groups.push(group.gid);

                    $GroupListLi.after(GroupList.addUserLi(group.gid, index));
                    GroupList.sortGroups();
                    GroupList.initgroup.resolve(result);
                });
			}
		);
    },

    showFavoriteGroups: function() {
        $.get(
	        OC.generateUrl('/apps/sharing_group/getFavoriteGroups'),
            function(result) {
                $('.loading').remove();
                $.each(result.data, function(index, group) {
                GroupList.groups.push(group.gid);
                GroupList.groups_name.push(index);

                $GroupListLi.after(GroupList.addLi(group.gid, index, group.count, group.user));
                GroupList.sortGroups();
                GroupList.initgroup.resolve(result);
            });
        });
    },
    
    refreshGroupList: function() {
        $.get(
			OC.generateUrl('/apps/sharing_group/getAllGroupsInfo'),
			function(result) {
			    $.each(result.data, function(index, group) {
                    if(group.user != null) {
                        user = group.user.split(",", group.count);
                        $('#group-list').data(group.id, user);
                    }
                    else {
                        $('#group-list').data(group.id, []);
                    }
                    $('#' + group.name).find('.user-count').text($('#group-list').data(group.id).length); 
                });
			}
		).done(function() {
            $.each($groupList.find('li'),function(index, group) {
                if($(group).hasClass('active') && $(group).data('gid') != '_everyone') {
                    GroupList.showGroup(GroupList.getElementGID(group));
                }
            });
        });

    },
    
    controlGroupUsers: function(multiGroup) {
        $.post(
            OC.generateUrl('/apps/sharing_group/controlGroupUser'),
            {
                multigroup: multiGroup
            },
            function(result) {
                if(result.status == 'success') {
                    GroupList.refreshGroupList();                    
                    OC.Notification.showTemporary(t(appname, "Your change are success"));
                }
            }
            ,'json'
        );
    },
    
    createGroup: function(groupname) {
		$.post(
			OC.generateUrl('/apps/sharing_group/create'),
			{
				name: groupname,
			},
			function(result) {
                if (result.status == 'success') {
				    $GroupListLi.after(GroupList.addLi(result.gid, groupname, 0, null));
                    GroupList.groups_name.push(groupname);
                    GroupList.sortGroups();
                }
                else {
				    OC.dialogs.alert(t(appname, 'Group already exists'), t(appname, 'Error create group'));
                }
				GroupList.toggleAddGroup();
		});
	},

    createGroupUsers : function(user, groupId) {
        $.post(
            OC.generateUrl('/apps/sharing_group/joinGroup'),
            {
                user: user,
                groupId: groupId
            },
            function (result) {
                if (result.status == 'success') {
				    $GroupListLi.after(GroupList.addUserLi(groupId, user));
                    GroupList.sortGroups();
                }
                else {
				    OC.dialogs.alert(t(appname, 'User already exists'), t(appname, 'Error join user'));
                }
				GroupList.toggleAddGroup();
            });
    },

    sortGroups: function() {
		var lis = $groupList.find('.isgroup').get();

		lis.sort(function(a, b) {
			// "Everyone" always at the top
			if ($(a).data('gid') === '_everyone') {
				return -1;
			} else if ($(b).data('gid') === '_everyone') {
				return 1;
			}

			if ($sortGroupBy === 1) {
				// Sort by user count first
				var $usersGroupA = $(a).data('user-count');
				var	$usersGroupB = $(b).data('user-count');
				if ($usersGroupA > 0 && $usersGroupA > $usersGroupB) {
					return -1;
				}
				if ($usersGroupB > 0 && $usersGroupB > $usersGroupA) {
					return 1;
				}
			}

			// Fallback or sort by group name
			return UserList.alphanum(
				$(a).find('a span').text(),
				$(b).find('a span').text()
			);
		});

		var items = [];
		$.each(lis, function(index, li) {
			items.push(li);
			if (items.length === 100) {
				$groupList.append(items);
				items = [];
			}
		});
		if (items.length > 0) {
			$groupList.append(items);
		}
	},

    addGroupToFavorite: function (gid) {
        $.post(
	        OC.generateUrl('/apps/sharing_group/addFavoriteGroup'),
			{
                user: oc_current_user,
				groupId: gid
			}, 
            function(result) {
                if (result.status === 'success') {
				    // $GroupListLi.after(GroupList.addLi(result.gid, groupname, 0, null));
                    // GroupList.groups_name.push(groupname);
                    // GroupList.sortGroups();

                    OC.Notification.showTemporary(t(appname, 'add favotire group success'));
                }
                else {
                    OC.Notification.showTemporary(t(appname, 'add group to favorite failed'));
                }
        });
    },

    leaveGroupFromFavorite: function (gid){
        $.post(
	        OC.generateUrl('/apps/sharing_group/leaveFavoriteGroup'),
			{
                user: oc_current_user,
				groupId: gid
			}, 
            function(result) {
                $('.fileActionsMenu').remove();
                if (result.status === 'success') {
                    var index = '#' + GroupList.groups_name[GroupList.groups.indexOf(String(gid))];
                    $(index).remove();
                    OC.Notification.showTemporary(t(appname, 'delete favotire group success'));
                    // setTimeout(function() {
                    //     location.reload();
                    // }, 800);
                }
                else {
                    OC.Notification.showTemporary(t(appname, 'delete favotire group failed'));
                }
        });
    },
    
    deleteGroup: function (gid, groupname) {
        $.post(
	        OC.generateUrl('/apps/sharing_group/deleteGroup'),
			{
				gid: gid
			}, 
            function(result) {
                $('.fileActionsMenu').remove();
                if (result.status === 'success') {
                    var index = '#' + GroupList.groups_name[GroupList.groups.indexOf(String(gid))];
                    $(index).remove();
                    OC.Notification.showTemporary(t(appname, 'delete group success'));
                    // setTimeout(function() {
                    //     location.reload();
                    // }, 800);
                }
                else {
                    OC.Notification.showTemporary(t(appname, 'delete group failed'));
                }
            });
    },

    deleteGroupUser: function (uid, gid) {
        $.post(
	        OC.generateUrl('/apps/sharing_group/leaveGroup'),
			{
				user: uid,
                groupId: gid
			}, 
            function(result) {
                if (result.status === 'success') {

                    OC.Notification.showTemporary(t(appname, 'delete user success'));
                    setTimeout(function() {
                        location.reload();
                    }, 800);
                }
                else {
                    OC.Notification.showTemporary(t(appname, 'delete user failed'));
                }
            });
    },
};

$(function() {
	$groupList = $('#group-list');
    
	$GroupListLi = $('#group-list #everyone-group');
	$GroupListLi.after($('<div class="loading" style="height: 200px; visibility: visible;"></div>'));

    //GroupList.createGroupUsers();

    if (GroupList.$_GET('favorite') === "1") {
        GroupList.showFavoriteGroups();

    }
    else if (GroupList.$_GET('gid') === null) {
        GroupList.showGroupList();
    } 
    else {
        GroupList.showGroupUsers(GroupList.$_GET('gid'));
    }
    
    // Display or hide of Create Group List Element
	$('#newgroup-form').hide();
	$('#newgroup-initial').on('click', function(e) {
        GroupList.toggleAddGroup(e);
    });
    
	$(document).on('click', function(event) {
        if (!GroupList.isAddGroupButtonVisible() &&
			!GroupList.elementBelongsToAddGroup(event.target)) {
			GroupList.toggleAddGroup();
		}

        if(GroupList.isGroupActionMenuVisible) {
            $('.fileActionsMenu').remove();
        }
	});

    $('.breadcrumb').on('click', 'div.crumb.svg.ui-droppable', function () {
        location.href = location.protocol + '//' + location.host + location.pathname;
    });

    $('.grouptable').on('click', '.menuactions', function (event) {
        event.stopPropagation();

        if(GroupList.isGroupActionMenuVisible) {
            $('.fileActionsMenu').remove();
        }

        var menuClass = $('<div>').attr({
            class: 'fileActionsMenu popovermenu bubble open menu',
            id: 'groupActionMenu'
        });

        var TEMPLATE_MENU =
            '<ul>' +
            '<li>' +
            '<a href="#" class="menuitem action action-details permanent" id="action_delete"><img class="icon" src="../../../core/img/actions/delete.svg"></span><span>Delete</span></a>' +
            '</li>' +
            '<li style="margin: 20px 0">' +
            '<a href="#" class="menuitem action action-details permanent" id="action_rename"><img class="icon" src="../../../core/img/actions/rename.svg"></span><span>Rename</span></a>' +
            '</li>' +
            '<li style="margin: 20px 0">' +
            '<a href="#" class="menuitem action action-details permanent" id="action_favorite"><img class="icon" src="../../../apps/files/img/star.svg"></span><span>Add Favorite</span></a>' +
            '</li>' +
            '<li style="margin: 20px 0">' +
            '<a href="#" class="menuitem action action-details permanent" id="action_favorite_remove"><img class="icon" src="../../../apps/files/img/star.svg"></span><span>Remove Favorite</span></a>' +
            '</li>' +
            '</ul>';

        menuClass.css('min-height', '110px');
        menuClass.css('min-width', '150px');
        menuClass.append(TEMPLATE_MENU);

        //parentNode belongs which tr #id
        var id = '#' + this.parentNode.innerText;
        $(id).find('.menuactions').append(menuClass);
    });
    
    $('#newgroup-name').keyup(function(event) {
        var newgroupname = $('#newgroup-name');
        if ($.inArray(newgroupname.val(), GroupList.groups_name) > -1) {
            newgroupname.addClass("ui-status-error");
        }
        else {
            newgroupname.removeClass("ui-status-error");
        }
        
        if (!GroupList.isAddGroupButtonVisible() && event.keyCode === $.ui.keyCode.ESCAPE) {
			GroupList.toggleAddGroup();
		}
        if (event.which === $.ui.keyCode.ENTER && GroupList.isGroupNameValid(newgroupname.val()) && !newgroupname.hasClass('ui-status-error')) {
            if (GroupList.$_GET('gid') === null) {
                GroupList.createGroup(newgroupname.val());
            } else {
                GroupList.createGroupUsers(newgroupname.val(), GroupList.$_GET('gid'));
            }
        }

    });
	// Responsible for Creating Groups.
	$('#newgroup-form .button').click(function(event) {
		//event.preventDefault();
        var newgroupname = $('#newgroup-name');
        if(GroupList.isGroupNameValid(newgroupname.val()) && !newgroupname.hasClass('ui-status-error')) {
			if (GroupList.$_GET('gid') === null) {
                GroupList.createGroup(newgroupname.val());
            } else {
                GroupList.createGroupUsers(newgroupname.val(), GroupList.$_GET('gid'));
            }
        }
	});

    $('.grouptable').on('click', '#action_delete', function(event) {
        var group = $(this);
        if($(event.target).is('span')) {
			var id = group.find('span').closest('tr').data('gid');
            var groupname = group.find('.group-name').text();

            // OC.dialogs.confirm(t(appname, 'Are you sure delete group ')  + groupname, t(appname, 'Sharing_Group'),
            // function(result) {
            //     if (result === true) {
            //         GroupList.deleteGroup(id, groupname);
            //     }
            // }, true
            // );

            GroupList.deleteGroup(id, groupname);

        } 
    });

    $('.grouptable').on('click', '#action_favorite', function(event) {
        var group = $(this);
        if($(event.target).is('span')) {
			var gid = group.find('span').closest('tr').data('gid');
            var groupname = group.find('.group-name').text();

            GroupList.addGroupToFavorite(gid);
        } 
    });

    $('.grouptable').on('click', '#action_favorite_remove', function(event) {
        var group = $(this);
        if($(event.target).is('span')) {
			var gid = group.find('span').closest('tr').data('gid');
            var groupname = group.find('.group-name').text();

            GroupList.leaveGroupFromFavorite(gid);
        } 
    });

    $('.grouptable').on('click', 'span.deletebutton', function(event) {
        //parentNode belongs which span.userlist
        var username = this.parentNode.innerText.toString();
        username = username.replace(/\n/, '');

        GroupList.deleteGroupUser(username, GroupList.$_GET('gid'));
    });

    $('.grouptable').on('click', 'span.group-name', function(event) {
        var index = GroupList.groups_name.indexOf(event.target.innerText);
        
        location.href = location.href.slice(0, location.href.lastIndexOf('/'));
        location.href = location.protocol + '//' + location.host + location.pathname + '?gid=' + GroupList.groups[index];
    });

    
    $('.sg-dropdown-scrollable').delegate('input:checkbox', 'change', function() {
        var checkbox = $(this);

        if(checkbox.data('origin') === 'unchecked' && checkbox.attr('checked') === undefined) {
            checkbox.tristate('state', false);
        }
        if(checkbox.data('origin') === 'checked' && checkbox.attr('checked') === undefined) {
            checkbox.tristate('state', false);
        }

        $(this).data('click',1);
       // event.stopPropagation();
    });

    $('#toggle-group').click(function(event) {
        $('.sg-dropdown-menu.checkuser').attr({hidden:true});
        $('.sg-dropdown-menu.group').attr({hidden:!$('.sg-dropdown-menu.group').attr('hidden')});
        $('.sg-dropdown-scrollable').find('li').remove();
        $.each($groupList.find('li'), function(index, group) {
            if ($(group).data('gid') != null && $(group).data('gid') != '_everyone') {
               GroupList.addCheckbox($(group).data('gid'), group.id)
            }
        });
        
        $.each($('.sg-dropdown-scrollable').find('li input'), function(index, group) {
            UserList.checktristate(group.id); 
        });

    });

    $('#favorite-sharegroups').click( function() { 
        location.href = location.href.slice(0, location.href.lastIndexOf('/'));
        location.href = location.protocol + '//' + location.host + location.pathname + '?favorite=1';
    });

    $('.nav-icon-mysharegroup').click( function() {     
        location.href = location.href.slice(0, location.href.lastIndexOf('/'));
        location.href = location.protocol + '//' + location.host + location.pathname;
    });
    
    $('#multi-group-select').click(function() {
        var multiGroup = {};

        $.each($('.sg-dropdown-scrollable').find('li input '), function(index, group) {
            var id = group.id.split('-')[1];
            var checked = $('#' + group.id).attr('checked'); 
            var indeterminate = $('#' + group.id).attr('indeterminate'); 
            var click = $('#' + group.id).data('click');
            
            var data_add = UserList.compareDifference($('#checkuser').data('checkeduser'),$('#group-list').data(id)); 
            var data_remove = UserList.compareSame(id);
            
            if (checked !== undefined && click === 1 ) {
                if (data_add.length !== 0) {
                    var action = 'add:' + data_add.join(",");
                    multiGroup[id] = action;
                }
            }
            
            if (checked === undefined && indeterminate === undefined && click === 1) {
                if (data_remove.length !== 0) {
                    var action = 'remove:' + data_remove.join(",");
                    multiGroup[id] = action;
                }
            }
        });
        if (!$.isEmptyObject(multiGroup)){
            GroupList.controlGroupUsers(multiGroup);
        }
        $('.sg-dropdown-menu').attr({hidden:true});
    });

    $('#cancel').click(function() {
        $('.sg-dropdown-menu').attr({hidden:true});
    });
	
	$('#newgroup-name').on('input', function() {
		GroupList.handleAddGroupInput(this.value);
	});
    
    $('.export').click(function() {
        var form = $('<form>').attr({
            action: OC.generateUrl('/apps/sharing_group/export'),
            method: 'GET'
        });
        form.trigger('submit');
    });
    
    $('.import').click(function() {
        $('#upload').trigger('click')
    });
    
    $('#upload').fileupload({
        url: OC.generateUrl('/apps/sharing_group/importGroup'),
        done:function(e,data) { 
            GroupList.showGroupList(data.result.gids);
        },
    });

});
