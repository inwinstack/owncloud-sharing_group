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
    
    compareDifference :function(array1, array2) {
        var difference = [];
        difference = $.grep(array1, function(el) {
            return $.inArray(el, array2) == -1;
        })
        
        return difference;
    },
    
    compareSame: function(gid) {
        var users = [];
        var length = 0;
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
            var div = $('<div>');
            var checkbox = $('<input>').attr({
                type: 'checkbox', 
                id: 'id-' + userId, 
                checked:false
            });
            var span = $('<span>').text(userName);
            var label = $('<label>').attr({
                for: 'id-' + userId, 
                class:'checkbox-card'
            });
                       
            label.append(checkbox);
            label.append(span);
            $userList.find('div').append(label);
        }
        else {
            var span = $('<span>').text(t(appname,'This group is empty'));
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
            user.closest('label').removeClass('checked');
        });
        $('#checkuser').data('checkeduser', []);
        UserList.checktristate();
    },
    
    checkAll: function() {
        $('#checkuser').data('checkeduser', $('#checkuser').data('user'));
        
        $.each($('#checkuser').data('user') , function(index, user) {
            var user = $('#id-' + user);
            
            user.attr({'checked':true});
            user.closest('label').addClass('checked');
        });
        UserList.checktristate();
    },
    
    quantity: function(offset, limit){
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
    
    init: function (users) {
        var userid = Object.keys(users.data);
        UserList.uid = UserList.uid.concat(userid);
        
        $('#checkuser').data({
            'user': UserList.uid,
            'checkeduser':[] ,
            'different': [],
            'origin': 'unchecked',
        });
        //$('#user-list').data('users',users.data);
    },
    
    update: function (gid, limit) {
        if(!limit) {
			limit = UserList.usersToLoad;
		}
		$userList.siblings('.loading').css('visibility', 'visible');
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
                    UserList.append(users, limit, gid);
                }
			});
    },
    
};

$(function () {
	$userList = $('#user-list');
         
	// Implements User Search
	filter = new UserManagementFilter($('#usersearchform input'), UserList, GroupList);
    
	$userList.after($('<div class="loading" style="height: 200px; visibility: hidden;"></div>'));
    // calculate initial limit of users to load
	var initialUserCountLimit = 100;
    $userList.delegate('input:checkbox', 'click' , function() {
        var checkboxForUser = $(this);
        //var name = checkboxForUser.closest('label').find('span').text();

        var id = checkboxForUser.closest('label').find('input').context.id.split('-')[1];
        
        checkboxForUser.prop('checked') ? checkboxForUser.attr({'checked':true}) :  checkboxForUser.attr({'checked':false});
        
        if (checkboxForUser.prop('checked')) {
            checkboxForUser.closest('label').addClass('checked');
            $('#checkuser').data('checkeduser').push(id);
        }
        else {
            checkboxForUser.closest('label').removeClass('checked');
            var index = $('#checkuser').data('checkeduser').indexOf(id);

            $('#checkuser').data('checkeduser').splice(index , 1);
        }
        UserList.checktristate();
    });
    
    $('#checkuser').click( function() {
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

    $('#toggle-checkbox').click(function(event) {
        $('.sg-dropdown-menu.checkuser').attr({
            hidden:!$('.sg-dropdown-menu.checkuser').attr('hidden')
        });
        $('.sg-dropdown-menu.group').attr({hidden:true});
    });

    $(document).on('click', function(event) {
        if ($(event.target).closest('.sg-dropdown').length != 1  && $(event.target).closest('#sg-dropdown-group').length != 1 ) {
            $('.sg-dropdown-menu').attr({hidden:true});
        }
    });

    $('#check-all').click(function() {
        UserList.checkAll();
        $('.sg-dropdown-menu').attr({hidden:true});
    });

    $('#clear-all').click(function() {
        UserList.clearAll();
        $('.sg-dropdown-menu').attr({hidden:true});
    });
    
    $('#inverse').click(function() {
        var checkusers = $('#checkuser').data('checkeduser');
        var difference = UserList.compareDifference($('#checkuser').data('user'), checkusers);
        
        $.each(checkusers , function(index, user) {
            var user = $('#id-' + user);
            
            user.attr({'checked':false});
            user.closest('label').removeClass('checked');
        });
        
        $('#checkuser').data('checkeduser',difference);
        
        $.each($('#checkuser').data('checkeduser'), function(index, user) {
            var user = $('#id-' + user);
            
            user.attr({'checked':true});
            user.closest('label').addClass('checked');
        });
        
        UserList.checktristate();
        $('.sg-dropdown-menu').attr({hidden:true});
    });
    
    $('.load-part-users').click(function() {
	    if(!!UserList.noMoreEntries) {
            return;
        }
        UserList.update(UserList.currentGid, initialUserCountLimit);
    });

    $('.load-all-users').click(function() {
        
        if(!!UserList.noMoreEntries) {
            return;
        }
        UserList.update(UserList.currentGid, parseInt($('#everyone-count').text()));
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
