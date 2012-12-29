<?php

namespace Users;

class Controller_Admin_Users_Permissions extends \Controller_Admin 
{

    public function before()
    {
        parent::before();

        \Theme::instance()->set_partial('subnavigation', 'partials/permissions_subnavigation');

        /*if(!\Warden::can(array('read'), 'users'))
        {
            \Messages::warning('Ups. You have not the permission to do this action.');
            \Fuel\Core\Response::redirect('admin');
        } */    
    }

	public function action_index()
	{
            
        $config = array(
                    'pagination_url' => \Fuel\Core\Uri::base().'admin/users/permissions/index/',
                    'total_items' => count(\Warden\Model_Permission::find('all')),
                    'per_page' => 20,
                    'uri_segment' => 4

                    );

        $pagination = \Pagination::forge('permissions_pagination', $config);
		$data['permissions'] = \Warden\Model_Permission::find('all', array(
                                                            'limit' => $pagination->per_page,
                                                            'offset' => $pagination->offset)
                                                         );
                        
        $data['pagination'] = $pagination->render();
                
        return \Theme::instance()
                ->get_template()
                ->set(  'title', 'Manage Permissions')
                ->set(  'content', 
                        \Theme::instance()->view('admin/permissions/index', $data)
                    );
	}
 
	public function action_create($id = null)
	{
        /*if(!\Warden::can(array('create'), 'users'))
        {
            \Messages::warning('Ups. You have not the permission to do this action.');
            \Fuel\Core\Response::redirect('admin');
        }*/

        $user = new \Warden\Model_User();
        
        $roles = \Warden\Model_Role::find()->get();
        
        $userroles = array();
        
        foreach($roles as $key => $value)
        {             
            $userroles[$key] = $value->name;
        }
                
		if (\Input::method() == 'POST')
		{
			
            
            $val = \Validation::forge();
            $val->add_callable('myvalidation');
            $val->add_field('username', 'Username', 'required|min_length[3]|max_length[20]|unique[users.username]');
            $val->add_field('password', 'Password', 'required|min_length[6]|max_length[20]');
            $val->add_field('email', 'E-Mail', 'required|valid_email|unique[users.email]');
            if ( $val->run() )
            {
                $user = new \Warden\Model_User(array(
                        'username' => $val->validated('username'),
                        'password' => $val->validated('password'),
                        'email'	   => $val->validated('email'),
                ));

                if( $user->save() )
                {
                    foreach (\Input::post('role') as $selected_role) 
                    {
                        //\Debug::dump("post: ",$selected_role);
                        $user->roles[$selected_role] = \Model_Role::find((int)$selected_role);
                    }
                    $user->save();
                    \Messages::success('Account successfully created.');
                    \Response::redirect('admin/users');
                }
                else
                {
                    \Messages::error('Ups. Something going wrong, please try again.');
                }
            }
            else
            {
                    \Messages::error($val->error());
            }
        }
        
        $data['user'] = $user;
        $data['roles'] = $userroles;

        return \Theme::instance()
                ->get_template()
               // ->set(  'title', 'Create User')
                ->set(  'content', 
                        \Theme::instance()->view('admin/users/create', $data)
                    );
	}
   
    public function action_edit($id = null)
	{
        /*if(!\Warden::can(array('update'), 'users'))
        {
            \Messages::warning('Ups. You have not the permission to do this action.');
            \Fuel\Core\Response::redirect('/');
        }*/
        
        $user   = \Warden\Model_User::find_by_id($id);
        $roles  = \Warden\Model_Role::find()->get();

        $userroles = array();
        foreach($roles as $key => $value)
        {
            $userroles[$key] = $value->name;
        }
            
        if (\Input::method() == 'POST')
        {
            $user = \Warden\Model_User::find_by_id($id);
           
            $val = \Validation::forge();
            $val->add_callable('myvalidation');

            if(\Input::post('username'))
            {
                $val->add_field('username', 'Username', 'required|min_length[3]|max_length[20]');
            }

            if(\Input::post('email'))
            {
                $val->add_field('email', 'E-Mail', 'required|valid_email');
            }

            if($val->run())
            {
                
                $user->username        = \Input::post('username');
                $user->email	       = \Input::post('email');
                $user->is_confirmed    = (\Input::post('is_confirmed') == 1) ? 1 : 0;
                
                if(\Input::post('password'))
                {
                    $user->encrypted_password  =  \Warden::encrypt_password( \Input::post('password') );
                }

                try
                {
                    foreach (\Input::post('role') as $selected_role) 
                    {
                        if(isset($user->roles[$selected_role]))
                        {
                            unset($user->roles);
                        }
                        $user->roles[$selected_role] = \Model_Role::find((int)$selected_role);
                    }
                        
                    if($user->save())
                    {
                        \Messages::success('Updated user #' . $id);
                        \Response::redirect('admin/users');
                    }
                    else
                    {
                        \Messages::warning("Nothing changed.");
                    }
                    
                }
                catch (\Orm\ValidationFailed $e)
                {
                    \Messages::error($e->getMessage());
                }
            } 
            else
            {
                \Messages::error($val->error());
            }       
        }

            
        \Breadcrumb::set("Edit User: ".$user->username,"",3);
        $data['user'] = $user;
        $data['roles'] = $userroles;

        return \Theme::instance()
                ->get_template()
                ->set(  'content', 
                        \Theme::instance()->view('admin/users/edit', $data)
                    );

	}
            
	public function action_delete($id = null)
	{
        /*if(!\Warden::can(array('delete'), 'users'))
        {
            \Messages::warning('Ups. You have not the permission to do this action.');
            \Fuel\Core\Response::redirect('/');
        }*/

		if ($user = \Warden\Model_Permissions::find_by_id($id))
		{
			$user->delete();

			\Messages::success('Deleted permission #'.$id);
		}
		else
		{
			\Messages::error('Could not delete permission #'.$id);
		}
                
        \Response::redirect('admin/users/permissions');        
	}
}