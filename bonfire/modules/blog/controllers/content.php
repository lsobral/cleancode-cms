<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class content extends Admin_Controller {

	//--------------------------------------------------------------------


	public function __construct()
	{
		parent::__construct();

		$this->auth->restrict('Blog.Content.View');
		$this->load->model('blog_model', null, true);
		$this->lang->load('blog');
		
			Assets::add_js(Template::theme_url('js/editors/tiny_mce/tiny_mce.js'));
			Assets::add_js(Template::theme_url('js/editors/tiny_mce/tiny_mce_init.js'));
		Template::set_block('sub_nav', 'content/_sub_nav');
	}

	//--------------------------------------------------------------------



	/*
		Method: index()

		Displays a list of form data.
	*/
	public function index()
	{

		// Deleting anything?
		if (isset($_POST['delete']))
		{
			$checked = $this->input->post('checked');

			if (is_array($checked) && count($checked))
			{
				$result = FALSE;
				foreach ($checked as $pid)
				{
					$result = $this->blog_model->delete($pid);
				}

				if ($result)
				{
					Template::set_message(count($checked) .' '. lang('blog_delete_success'), 'success');
				}
				else
				{
					Template::set_message(lang('blog_delete_failure') . $this->blog_model->error, 'error');
				}
			}
		}

		$records = $this->blog_model->find_all();

		Template::set('records', $records);
		Template::set('toolbar_title', 'Manage blog');
		Template::render();
	}

	//--------------------------------------------------------------------



	/*
		Method: create()

		Creates a blog object.
	*/
	public function create()
	{
		$this->auth->restrict('Blog.Content.Create');

		if ($this->input->post('save'))
		{
			if ($insert_id = $this->save_blog())
			{
				// Log the activity
				$this->activity_model->log_activity($this->current_user->id, lang('blog_act_create_record').': ' . $insert_id . ' : ' . $this->input->ip_address(), 'blog');

				Template::set_message(lang('blog_create_success'), 'success');
				Template::redirect(SITE_AREA .'/content/blog');
			}
			else
			{
				Template::set_message(lang('blog_create_failure') . $this->blog_model->error, 'error');
			}
		}
		Assets::add_module_js('blog', 'blog.js');

		Template::set('toolbar_title', lang('blog_create') . ' blog');
		Template::render();
	}

	//--------------------------------------------------------------------



	/*
		Method: edit()

		Allows editing of blog data.
	*/
	public function edit()
	{
		$id = $this->uri->segment(5);

		if (empty($id))
		{
			Template::set_message(lang('blog_invalid_id'), 'error');
			redirect(SITE_AREA .'/content/blog');
		}

		if (isset($_POST['save']))
		{
			$this->auth->restrict('Blog.Content.Edit');

			if ($this->save_blog('update', $id))
			{
				// Log the activity
				$this->activity_model->log_activity($this->current_user->id, lang('blog_act_edit_record').': ' . $id . ' : ' . $this->input->ip_address(), 'blog');

				Template::set_message(lang('blog_edit_success'), 'success');
			}
			else
			{
				Template::set_message(lang('blog_edit_failure') . $this->blog_model->error, 'error');
			}
		}
		else if (isset($_POST['delete']))
		{
			$this->auth->restrict('Blog.Content.Delete');

			if ($this->blog_model->delete($id))
			{
				// Log the activity
				$this->activity_model->log_activity($this->current_user->id, lang('blog_act_delete_record').': ' . $id . ' : ' . $this->input->ip_address(), 'blog');

				Template::set_message(lang('blog_delete_success'), 'success');

				redirect(SITE_AREA .'/content/blog');
			} else
			{
				Template::set_message(lang('blog_delete_failure') . $this->blog_model->error, 'error');
			}
		}
		Template::set('blog', $this->blog_model->find($id));
		Assets::add_module_js('blog', 'blog.js');

		Template::set('toolbar_title', lang('blog_edit') . ' blog');
		Template::render();
	}

	//--------------------------------------------------------------------


	//--------------------------------------------------------------------
	// !PRIVATE METHODS
	//--------------------------------------------------------------------

	/*
		Method: save_blog()

		Does the actual validation and saving of form data.

		Parameters:
			$type	- Either "insert" or "update"
			$id		- The ID of the record to update. Not needed for inserts.

		Returns:
			An INT id for successful inserts. If updating, returns TRUE on success.
			Otherwise, returns FALSE.
	*/
	private function save_blog($type='insert', $id=0)
	{
		if ($type == 'update') {
			$_POST['post_id'] = $id;
		}

		
		$this->form_validation->set_rules('blog_title','Title','required|max_length[255]');
		$this->form_validation->set_rules('blog_slug','Slug','required|max_length[255]');
		$this->form_validation->set_rules('blog_body','Text','required');

		if ($this->form_validation->run() === FALSE)
		{
			return FALSE;
		}

		// make sure we only pass in the fields we want
		
		$data = array();
		$data['blog_title']        = $this->input->post('blog_title');
		$data['blog_slug']        = $this->input->post('blog_slug');
		$data['blog_body']        = $this->input->post('blog_body');

		if ($type == 'insert')
		{
			$id = $this->blog_model->insert($data);

			if (is_numeric($id))
			{
				$return = $id;
			} else
			{
				$return = FALSE;
			}
		}
		else if ($type == 'update')
		{
			$return = $this->blog_model->update($id, $data);
		}

		return $return;
	}

	//--------------------------------------------------------------------



}