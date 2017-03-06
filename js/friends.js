var $userList,
    $userListUl,
    filter,
    appname = 'sharing_group';

var UserList = {
	availableGroups: [],
	offset: 0,
    length: 0,
    user: $.Deferred(),
	usersToLoad: 100,
	currentGid: '',
    uid: [],

	preSortSearchString: function(a, b) {
		var pattern = filter.getPattern();
		var aMatches = false;
		var bMatches = false;

        if(typeof pattern === 'undefined') {
			return undefined;
		}
		pattern = pattern.toLowerCase();
		if(typeof a === 'string' && a.toLowerCase().indexOf(pattern) === 0) {
			aMatches = true;
		}
		if(typeof b === 'string' && b.toLowerCase().indexOf(pattern) === 0) {
			bMatches = true;
		}
		if((aMatches && bMatches) || (!aMatches && !bMatches)) {
			return undefined;
		}
		if(aMatches) {
			return -1;
		} else {
			return 1;
		}
	},
	
    sortUser: function() {
		var labels = $userList.find('label').get();

		labels.sort(function(a, b) {
            var aId = $(a).find('input')[0].id.split('-')[1];
		    var bId = $(b).find('input')[0].id.split('-')[1];
            
            // Fallback or sort by group name
			return UserList.alphanum(
				aId,
				bId
			);
		});

		var items = [];
		$.each(labels, function(index, label) {
			items.push(label);
			if (items.length === 100) {
				$userList.append(items);
				items = [];
			}
		});
		if (items.length > 0) {
			$userList.append(items);
		}
	},

    // From http://my.opera.com/GreyWyvern/blog/show.dml/1671288
	alphanum: function(a, b) {
		function chunkify(t) {
			var tz = [], x = 0, y = -1, n = 0, i, j;

			while (i = (j = t.charAt(x++)).charCodeAt(0)) {
				var m = (i == 46 || (i >=48 && i <= 57));
				if (m !== n) {
					tz[++y] = "";
					n = m;
				}
				tz[y] += j;
			}
			return tz;
		}

		var aa = chunkify(a.toLowerCase());
		var bb = chunkify(b.toLowerCase());

		for (var x = 0; aa[x] && bb[x]; x++) {
			if (aa[x] !== bb[x]) {
				var c = Number(aa[x]), d = Number(bb[x]);
				if (c == aa[x] && d == bb[x]) {
					return c - d;
				} else {
					return (aa[x] > bb[x]) ? 1 : -1;
				}
			}
		}
		return aa.length - bb.length;
	},
    
    empty: function() {
		//one row needs to be kept, because it is cloned to add new rows
		$userList.find('label').remove();
        UserList.uid = [];
		UserList.offset = 0;
        UserList.length = 0;
	},
    
    isdisabled :function() {
        var user = $('#checkuser').data('checkeduser');
        if(user.length > 0) {
            $('#sg-deletefriend').removeAttr('disabled');
            $('#sg-dropdown-group').show();
        }
        else {
            $('#sg-dropdown-group').hide();
            $('#sg-deletefriend').attr('disabled','disabled');
        }
    },
    
    compareDifference :function(array1, array2) {
        var difference = [];
        difference = $.grep(array1, function(el) {
            return $.inArray(el, array2) == -1;
        })
        
        return difference;
    },
    
    compareSame: function(gid) {
        var users = [];
        $('#group-list').data(gid).filter(function(user) {
            $.each($('#checkuser').data('checkeduser'), function(index, checkuser) {
                if (user == checkuser) {
                    users.push(user);
                }
            });
        });
        return users;
    },
    
    checktristate: function(groupid) {
        var checkLength = $('#checkuser').data('checkeduser').length;
        var userLength = $('#checkuser').data('user').length;
        var $tristate = $('#checkuser').tristate();
        
        if(groupid != undefined) { 
            var id = groupid.split('-')[1];
            var groupLength = $('#group-list').data(id).length;
            var $tristate = $('#id-' + id);
            var sameLength = UserList.compareSame(id).length;    
            
            if (sameLength == checkLength && checkLength != 0) {
                $tristate.tristate('state', true);
                $tristate.data('origin','checked');
            }
            else if (sameLength != 0 && sameLength != checkLength) {
                $tristate.tristate('state', null);
                $tristate.data('origin','indeterminate');
            }
            else if (sameLength == 0) {
                $tristate.tristate('state', false);
                $tristate.data('origin','unchecked');
            }
        }
        else {
            if (checkLength == 0) {
                $tristate.tristate('state', false);
                $tristate.data('origin','unchecked');
            }
            else if (checkLength == userLength)  {
                $tristate.tristate('state', true);
                $tristate.data('origin','checked');
            }
            else if (checkLength != 0 && checkLength != userLength) {
                $tristate.tristate('state', null);
                $tristate.data('origin','indeterminate');
            }
        }
    },
    
    addLabel: function(userId, userName) {
        if(userId != undefined) {
            var divAvatar = $('<div>').attr({
                class:'avatardiv sg-icon'
            });
            divAvatar.avatar(userId,32)
            var divInfo = $('<div>').attr('class','sg-info');
            var checkbox = $('<input>').attr({
                type: 'checkbox', 
                id: 'id-' + userId, 
                checked:false,
                class:'sg-checkbox'
            });
            var username = $('<input>').attr({
                type: 'text',
                class:'sg-username'
            });
            username.val(userName);
            var span = $('<span>').text(userId);
            var label = $('<label>').attr({
                for: 'id-' + userId, 
                class:'sg-user'
            });
            label.data('name',userName);
            divInfo.append(checkbox, username, span);
            label.append(divAvatar);
            label.append(divInfo);
            $userList.find('.sg-userlist').append(label);
        }
        else {
            if(UserList.currentGid == '_everyone') {
                var span = $('<span>').text(t(appname,"You do not add any friend yet."));
            }
            else {
                var span = $('<span>').text(t(appname,'This group is empty'));
            }
            var label = $('<label>');
                    
            label.append(span);
            $userList.find('div').append(label);
        }
    },
	
    clearAll: function() {
        $.each($('#checkuser').data('checkeduser'), function(index, user) {
            var user = $('#id-' + user);
            
            user.attr({
                'checked':false
            });
            
            user.closest('label').find('img').remove('.img-check');
            user.closest('label').removeClass('checked');
        });
        $('#checkuser').data('checkeduser', []);
        UserList.isdisabled();
        UserList.checktristate();
    },
    
    checkAll: function() {
        var alluser = [];
        $('#checkuser').data('checkeduser', alluser.concat(UserList.uid));
        
        $.each($('#checkuser').data('user') , function(index, user) {
            var user = $('#id-' + user);
            var img = $('<img>').attr({
                'src' : OC.imagePath('sharing_group','check.png'),
                'class' : 'img-check'
            });
            var label = user.closest('label');
            user.attr({'checked':true});
            label.find('.avatardiv').append(img);
            label.addClass('checked');
        });
        UserList.isdisabled();
        UserList.checktristate();
    },
    
    quantity: function(offset, limit){
        if(offset != limit) {
            $('.load-part-users').removeAttr('disabled');
            $('.load-all-users').removeAttr('disabled');
        }
        else {
            $('.load-part-users').attr('disabled','disabled');
            $('.load-all-users').attr('disabled','disabled');
        }

        $('.users-offset').text(offset);
        $('.all-users-count').text(limit);
    },
    
    append: function(users, limit, gid) {
        if(!limit) {
			limit = UserList.usersToLoad;
		}
        $.each(users.data, function (userId, userName) {
            if(userName == null) {
                userName = userId;
            }
            UserList.addLabel(userId,userName);
        });
        if($('#checkuser').data('user').length == 0) {
            UserList.addLabel();
        }
        if (users.length > 0) {
            $userList.siblings('.loading').css('visibility', 'hidden');
            // reset state on load
            UserList.noMoreEntries = false;
        }
        else {
            UserList.noMoreEntries = true;
            $userList.siblings('.loading').css('visibility', 'hidden');
        }
        UserList.offset += limit;
        UserList.length += users.length;
        if(gid == '_everyone') {
            UserList.quantity(UserList.length, $('#everyone-count').text());
        }
        else {
            UserList.quantity(UserList.length, $('#group-list').data(gid).length);
        }
    },

    countFriends: function() {
        $.get(
			OC.generateUrl('/apps/sharing_group/countFriends'),
			function (everyoneCount) {
                $('#everyone-count').text(everyoneCount.length);
            });
    },
    
    init: function (users) {
        var userid = Object.keys(users.data);
        var statereset = $('#checkuser').attr('checked') !== undefined || $('#checkuser').attr('indeterminate') !== undefined;
        
        UserList.uid = UserList.uid.concat(userid);
        $('.user-listed').show();
        $('#sg-dropdown-group').hide();
        
        if(statereset){
            $('#checkuser').tristate('state', false);
        }
        $('#checkuser').data({
            'user': UserList.uid,
            'checkeduser':[] ,
            'different': [],
            'origin': 'unchecked',
        });
    },
    
    update: function (gid, limit) {
        if(UserList.updating == gid) {
            return;
        }
        if(!limit) {
			limit = UserList.usersToLoad;
		}
		$userList.siblings('.loading').css('visibility', 'visible');
		UserList.updating = gid;
        if(gid === undefined) {
			gid = '_everyone';
		}
        UserList.currentGid = gid;
        var pattern = filter.getPattern();
        $.get(
			OC.generateUrl('/apps/sharing_group/user'),
			{ 
                offset: UserList.offset, 
                limit: limit,
                gid: gid, 
                pattern: pattern 
            },
			function (users) {
                if(UserList.currentGid == '') {
                    UserList.user.resolve(users);
					$userList.siblings('.loading').css('visibility', 'hidden');
                    return;
                }
                if(UserList.currentGid == gid) {
                    UserList.init(users);
                    UserList.empty();
                    UserList.append(users, limit, gid);
                }
			}).always(function () {
                UserList.updating = undefined;
            });
    },
    
    adjustControlsWidth: function() {
        if($('#controls').length) {
            var controlsWidth;
            // if there is a scrollbar …
            if($('#app-content').get(0).scrollHeight > $('#app-content').height()) {
                if($(window).width() > 768) {
                    controlsWidth = $('#content').width() - $('#app-navigation').width() - OC.Util.getScrollBarWidth();
                    if (!$('#app-sidebar').hasClass('hidden') && !$('#app-sidebar').hasClass('disappear')) {
                        controlsWidth -= $('#app-sidebar').width();
                    }
                } else {
                    controlsWidth = $('#content').width() - OC.Util.getScrollBarWidth();
                }
            } else { // if there is none
                if($(window).width() > 768) {
                    controlsWidth = $('#content').width() - $('#app-navigation').width();
                    if (!$('#app-sidebar').hasClass('hidden') && !$('#app-sidebar').hasClass('disappear')) {
                        controlsWidth -= $('#app-sidebar').width();
                    }
                } else {
                    controlsWidth = $('#content').width();
                }
            }
            $('#controls').css('width', controlsWidth);
            $('#controls').css('min-width', controlsWidth);
        }
    },
};

$(function () {
	$userList = $('#user-list');
    // calculate initial limit of users to load
	var initialUserCountLimit = 100;
    UserList.adjustControlsWidth();
	// Implements User Search
	filter = new UserManagementFilter($('#usersearchform input'), UserList, GroupList);
    
	$userList.after($('<div class="loading" style="height: 200px; visibility: hidden;"></div>'));
    
    $userList.delegate('input:checkbox', 'click' , function() {
        var checkboxForUser = $(this);
        var label = $(this).closest('label');
        var id = label.find('input').context.id.split('-')[1];
        var img = $('<img>').attr({
            'src' : OC.imagePath('sharing_group','check.png'),
            'class' : 'img-check'
        });
   
        checkboxForUser.prop('checked') ? checkboxForUser.attr({'checked':true}) :  checkboxForUser.attr({'checked':false});
        
        if (checkboxForUser.prop('checked')) {
            label.addClass('checked');
            $('#checkuser').data('checkeduser').push(id);
            label.find('.avatardiv').append(img);
        }
        else {
            label.removeClass('checked');
            var index = $('#checkuser').data('checkeduser').indexOf(id);
            label.find('img').remove('.img-check');
            $('#checkuser').data('checkeduser').splice(index , 1);
        }
        
        UserList.isdisabled();
        UserList.checktristate();
    });
    
    $('#checkuser').click(function(event) {
        var originState = $('#checkuser').data('origin'); 
        var checkuser = $('#checkuser'); 
        
        if (originState == 'indeterminate') {
            UserList.clearAll();
            checkuser.tristate('state', null);
        }
        else if (originState == 'unchecked') {
            UserList.checkAll();
            checkuser.tristate('state', false);
        }
        else if (originState == 'checked') {
            UserList.clearAll();
            checkuser.tristate('state', null);
        }
        
        event.stopPropagation();
    });

    $('.sg-dropdown').click(function(event) {
        var menu = $(event.target).parents('.sg-dropdown').find('.sg-dropdown-menu');
        if(menu.is(':visible')) {
            menu.hide();
        }
        else {
            menu.show();
        }
    });
    
    $(document).on('click', function(event) {
        var searchfriend = $('#sg-searchfriend-searchbox');
        var target = $(event.target);
        if(!searchfriend.is(":focus") && searchfriend.data('isSearched') == false) {
            searchfriend.val('');
            searchfriend.removeClass("focus");
            searchfriend.data('isSearched',false);
            $('#sg-searchfriend-cancel').hide();
        }
        if (!target.parents('#sg-dropdown-group').length) {
            $('.sg-dropdown-menu.group').hide();
        }
        if (!target.parents('#sg-dropdown-checkuser').length) {
            $('.sg-dropdown-menu.checkuser').hide();
        }
        if (!target.parents('#sg-dropdown-load').length) {
            $('.sg-dropdown-menu.load').hide();
        }

    });

    $('#check-all').click(function() {
        UserList.checkAll();
    });

    $('#clear-all').click(function() {
        UserList.clearAll();
    });
    
    $('#inverse').click(function() {
        var checkusers = $('#checkuser').data('checkeduser');
        var difference = UserList.compareDifference($('#checkuser').data('user'), checkusers);
        $.each(checkusers , function(index, user) {
            var user = $('#id-' + user);
            var label = user.closest('label');
            user.attr({'checked':false});
            label.find('img').remove('.img-check');
            label.removeClass('checked');
        });
        
        $('#checkuser').data('checkeduser',difference);
        
        $.each($('#checkuser').data('checkeduser'), function(index, user) {
            var user = $('#id-' + user);
            var label = user.closest('label');
            var img = $('<img>').attr({
                'src' : OC.imagePath('sharing_group','check.png'),
                'class' : 'img-check'
            });

            user.attr({'checked':true});
            label.find('.avatardiv').append(img);
            label.addClass('checked');
        });
        
        UserList.isdisabled();
        UserList.checktristate();
    });
    
    $('.load-part-users').click(function(event) {
        if(!!UserList.noMoreEntries || $(this).attr('disabled') == 'disabled') {
            
            return;
        }
        UserList.update(UserList.currentGid, initialUserCountLimit);
    });

    $('.load-all-users').click(function() {
        if(!!UserList.noMoreEntries || $(this).attr('disabled') == 'disabled') {
            
            return;
        }
        UserList.update(UserList.currentGid, parseInt($('#everyone-count').text()));
    });
    
    $('#sg-dialog').dialog({
        autoOpen:false,
        modal:true,
        buttons: [{
            text: t(appname,"Add"),
            id: 'sg-add',
            click: function() {
                $.get(
                    OC.generateUrl('/apps/sharing_group/addFriend'),
                    {
                        uid: $('.sg-friend-name').data('id')
                    },
                    function(user) {
                        if(user.status === 'error') {
                            OC.Notification.showTemporary(t(appname, user.message));
                            
                            return;
                        }
                        var everyone_count = $('#everyone-count');
                        var count = parseInt(everyone_count.text());
                        
                        if(count == 0) {
		                    UserList.empty();
                        }
                        
                        everyone_count.text(count+1);
                        UserList.length += 1;
                        UserList.quantity(UserList.length, count+1);

                        if(UserList.currentGid != '_everyone') {
                            GroupList.showGroup('_everyone');
                            return ;
                        }

                        UserList.init(user);
                        $.each(user.data, function( Uid, Name) {
                            UserList.addLabel(Uid,Name);
                        });
                    });
            }
            },
            {
            text: t(appname,"Cancel"),
            click: function() {
                $('#sg-dialog').dialog("close");
            }
            }],
        open: function() {
            $('#sg-add').button('disable');
        },
        close: function() {
            $('.sg-friend-name').data('id','');
            $('.sg-friend-name').text("");
            $('#sg-addfriend-searchbox').val("");
        }
    });
    
    $('#sg-addfriend').click(function() {
        $('.oc-dialog').hide();
        if($('#sg-dialog').is(':visible')) {
           
            return;
        }
        $('#sg-dialog').dialog("open");
    });
    
    $('#sg-deletefriend').click(function() {
            
        $('#sg-dialog').dialog("close");
        var dialog = $('<div>').dialog({
            autoOpen: false,
            modal: true,
            title: t(appname, 'Delete friends'),
            buttons: [{
                text: t(appname,"Yes"),
                click: function() {
                    var users = $('#checkuser').data('checkeduser');
                    $.get(
                        OC.generateUrl('/apps/sharing_group/deleteFriends'),
                        { 
                            users: users.join()
                        },
                        function (result) {
                            if(result.status == 'success') {
                                $.each(users,function(index,user){
                                    $('#id-'+user).closest('label').remove();
                                })
                                var everyone_count = $('#everyone-count');
                                var delete_count = users.length;

                                UserList.length -= delete_count;
                                everyone_count.text(UserList.length);
                                UserList.quantity(UserList.length, UserList.length);
                                UserList.clearAll();
                                GroupList.refreshGroupList();
                                OC.Notification.showTemporary(t(appname, 'Deleting friends successfully.'));
                                dialog.dialog('close');
                            }
                        });
                } 
                },
                {
                text: t(appname,"Cancel"),
                click: function() {
                    dialog.remove();
                }
                }],
            close: function() {
                dialog.remove();
            }
       });
       var p = $('<p>').text(t(appname, 'Are you sure you want to delete selected friends?'));
       dialog.append(p);
       dialog.dialog('open');
    });

    $userList.on('change', '.sg-username', function() {
        var label = $(this).closest('.sg-user');
        var userid = label.find('span').text();
        var newname = $(this).val();
        if(newname == '') {
            $(this).focus();
            $(this).val(label.data('name'))
            OC.Notification.showTemporary(t(appname, 'Please input a vaild Nickname.'));
            return;
        }
        $.get(
            OC.generateUrl('/apps/sharing_group/rename'),
            { 
                userid: userid,
                nickname: newname
            },
            function (result) {
                if(result.status == 'success') {
                    label.data('name',newname)
                    OC.Notification.showTemporary(t(appname, 'Renaming nickname successfully.'));
                }
            });
    });
    $('#sg-searchfriend-searchbox').data('isSearched',false).click(function(event) {
        $(this).addClass("focus");
        $('#sg-searchfriend-cancel').show();    
    });

    $('#sg-searchfriend-searchbox').on('keyup', function(event) {
        var pattern = $(this).val();
        if(event.which == $.ui.keyCode.ENTER) {
            $.get(
                OC.generateUrl('/apps/sharing_group/search'),
                { 
                    pattern: pattern,
                    gid: UserList.currentGid
                },
                function (users) {
                    var label = $('<label>');
                    
                    UserList.init(users);
                    UserList.empty();
                    $('.user-listed').hide();
                    $('#sg-searchfriend-searchbox').data('isSearched',true);
                    if(users.data.length == 0) {
                        var span = $('<span>').text(t(appname,'No search results.'));
                        
                        label.append(span);
                        $userList.append(label);
                    }
                    else {
                        var span = $('<span>').text(users.length + t(appname,' friends match search results.'));
                        
                        $.each(users.data, function (userId, userName) {
                            UserList.addLabel(userId,userName);
                        });
                        label.append(span);
                        $userList.append(label);
                    }
                });
        }
    });
    
    $('#sg-searchfriend-cancel').click(function() {
        var searchbox = $('#sg-searchfriend-searchbox');
        
        $('.user-listed').show();
        searchbox.removeClass("focus");
        
        if(searchbox.data('isSearched') == true) {
            $('#group-list').find('li.active').trigger('click');
            searchbox.data('isSearched',false);
        }
        $(this).hide();
    });

    $('.sg-searchbox').submit(function(event) {
        var userID = $('#sg-addfriend-searchbox').val()
		event.preventDefault();
        $.get(
            OC.generateUrl('/apps/sharing_group/getUser'),
            { 
                userID: userID
            },
            function (user) {
                if(user.status === 'error') {
                    OC.Notification.showTemporary(t(appname, user.message));
                    $('.sg-friend-name').text("");
                    $('#sg-add').button('disable');
                    
                    return;
                }
                else {
                     
                    $('#sg-add').button('enable');
                    $.each(user.data, function( Uid, Name) {
                        $('.sg-friend-name').data('id',Uid);
                        $('.sg-friend-name').text(Name);
                    });
                }
            });
            
    });
    // trigger loading of users on startup
    UserList.update(UserList.currentGid, initialUserCountLimit);
    
    // the first loading
    $.when(UserList.user, GroupList.initgroup).done(function (users, groups){
	    $GroupListLi.siblings('.loading').remove();
        $.each(groups.data, function(index, group) {
            GroupList.groups.push(group.id);
            GroupList.groups_name.push(group.name);

            $GroupListLi.after(GroupList.addLi(group.id, group.name, group.count, group.user));
            GroupList.sortGroups();
        });
        
        UserList.init(users);

        if(UserList.currentGid == '') {
            UserList.currentGid = '_everyone';
            UserList.append(users, UserList.usersToLoad, UserList.currentGid);
        }
    });
});
