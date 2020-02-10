<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require(APPPATH . 'libraries/REST_Controller.php');

class Api extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Product_model');
        $this->load->library('session');
        $this->checklogin = $this->session->userdata('logged_in');
        $this->user_id = $this->session->userdata('logged_in')['login_id'];
    }

    public function index() {
        $this->load->view('welcome_message');
    }

    function updateCurd_post() {
        $fieldname = $this->post('name');
        $value = $this->post('value');
        $pk_id = $this->post('pk');
        $tablename = $this->post('tablename');
        if ($this->checklogin) {
            $data = array($fieldname => $value);
            $this->db->set($data);
            $this->db->where("id", $pk_id);
            $this->db->update($tablename, $data);
        }
    }

    //function for product list
    function loginOperation_get() {
        $userid = $this->user_id;
        $this->db->select('au.id,au.first_name,au.last_name,au.email,au.contact_no');
        $this->db->from('admin_users au');
        $this->db->where('id', $userid);
        $this->db->limit(1);
        $query = $this->db->get();
        $result = $query->row();
        $this->response($result);
    }

    //Login Function 
    //function for product list
    function loginOperation_post() {
        $email = $this->post('contact_no');
        $password = $this->post('password');
        $this->db->select('au.id,au.first_name,au.last_name,au.email,au.contact_no');
        $this->db->from('admin_users au');
        $this->db->where('contact_no', $email);
        $this->db->where('password', md5($password));
        $this->db->limit(1);
        $query = $this->db->get();
        $result = $query->row();

        $sess_data = array(
            'username' => $result->email,
            'first_name' => $result->first_name,
            'last_name' => $result->last_name,
            'login_id' => $result->id,
        );
        $this->session->set_userdata('logged_in', $sess_data);
        $this->response($result);
    }

    function registerMobileGuest_post() {
        $this->config->load('rest', TRUE);
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        $reg_id = $this->post('reg_id');
        $model = $this->post('model');
        $manufacturer = $this->post('manufacturer');
        $uuid = $this->post('uuid');
        $regArray = array(
            "reg_id" => $reg_id,
            "manufacturer" => $manufacturer,
            "uuid" => $uuid,
            "model" => $model,
            "user_id" => "Guest",
            "user_type" => "Guest",
            "datetime" => date("Y-m-d H:i:s a")
        );
        $this->db->where('reg_id', $reg_id);
        $query = $this->db->get('gcm_registration');
        $regarray = $query->result_array();
        if ($regArray) {
            
        } else {
            $this->db->insert('gcm_registration', $regArray);
        }
        $this->response(array("status" => "done"));
    }

    function getUserCard_get($userid) {
        $this->db->where('usercode', $userid);
        $query = $this->db->get('app_user');
        $userdata = $query->row();
        $imagepath = base_url() . "assets/usercard/" . $userdata->cardimage;
        $this->response(array("imagelink" => $imagepath, "userdata" => $userdata));
    }

    function createUserQrCode_get($userid) {
        $this->load->library('phpqr');
        $this->db->where('usercode', $userid);
        $query = $this->db->get('app_user');
        $userdata = $query->row();
        $filelocation = APPPATH . "../assets/userqr/" . $userdata->usercode . ".png";
        $linkdata = site_url("Api/getUserCard/" . $userdata->usercode);
        $this->phpqr->createcode($linkdata, $filelocation);
        $imagepath = base_url() . "assets/userqr/" . $userdata->usercode . ".png";
        $this->response(array("imagelink" => $imagepath));
    }

    function createUserCard_get($userid) {
        //Set the Content Type
        header('Content-type: image/jpeg');
        // Create Image From Existing File
        $jpg_image = imagecreatefromjpeg(APPPATH . "../assets/cardtemplate/card1.jpg");
        // Allocate A Color For The Text
        $white = imagecolorallocate($jpg_image, 255, 255, 255);
        $blue = imagecolorallocate($jpg_image, 1, 129, 161);

        // Set Path to Font File
        $font_path1 = APPPATH . "../assets/cardtemplate/fonts/Aaargh.ttf";

        $font_path2 = APPPATH . "../assets/cardtemplate/fonts/ABeeZee-Regular.otf";

        // Set Text to Be Printed On Image
        $text = "Pankaj Pathak";
        $this->db->where('usercode', $userid);
        $query = $this->db->get('app_user');
        $userdata = $query->row();
        $randid = rand(10000000, 99999999);
        $destination_image = APPPATH . "../assets/usercard/card1" . $userdata->id . $randid . ".jpg";
        $filelocation = APPPATH . "../assets/userqr/" . $userdata->usercode . ".png";
        $frame = imagecreatefrompng($filelocation);

        // Print Text On Image
        imagettftext($jpg_image, 65, 0, 130, 240, $white, $font_path2, $userdata->name);
        imagettftext($jpg_image, 40, 0, 130, 330, $blue, $font_path1, $userdata->designation);
        imagettftext($jpg_image, 28, 0, 280, 630, $white, $font_path2, $userdata->email);
        imagettftext($jpg_image, 28, 0, 280, 780, $white, $font_path2, $userdata->contact_no);
        imagettftext($jpg_image, 28, 0, 280, 930, $white, $font_path2, $userdata->company);
        imagettftext($jpg_image, 65, 0, 1250, 480, $blue, $font_path2, $userdata->company);
        imagecopymerge($jpg_image, $frame, 1400, 680, 0, 0, 800, 800, 100);
        // Send Image to Browser
        imagejpeg($jpg_image, $destination_image);
        $imagepath = base_url() . "assets/usercard/card1" . $userdata->id . $randid . ".jpg";

        $this->db->set("cardimage", "card1" . $userdata->id . $randid . ".jpg");
        $this->db->where('usercode', $userid);
        $this->db->update("app_user");


        $this->response(array("imagelink" => $imagepath));
    }

    function registration_post() {
        $this->config->load('rest', TRUE);
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        $name = $this->post('name');
        $contact_no = $this->post('contact_no');
        $email = "";
        $password = rand(1000, 9999);
        $usercode = rand(1000, 9999);
        $profileimageurl = "";
        $regArray = array(
            "name" => $name,
            "email" => $email,
            "contact_no" => $contact_no,
            "password" => $password,
            "usercode" => $usercode,
            "datetime" => date("Y-m-d H:i:s a"),
            "profile_image" => $profileimageurl,
            "cardimage" => "",
        );
        $this->db->where('contact_no', $contact_no);
        $query = $this->db->get('app_user');
        $userdata = $query->row();
        if ($userdata) {
            $regArray = array(
                "name" => $name,
            );
            $this->db->set($regArray);
            $this->db->where('contact_no', $contact_no); //set column_name and value in which row need to update
            $this->db->update("app_user");
            $this->response(array("status" => "200", "userdata" => $userdata));
        } else {
            $this->db->insert('app_user', $regArray);
            $last_id = $this->db->insert_id();
            $updateArray = array(
                "usercode" => "" . $usercode . $last_id,
            );
            $this->db->set($regArray);
            $this->db->where('id', $last_id); //set column_name and value in which row need to update
            $this->db->update("app_user");
            $this->response(array("status" => "200", "userdata" => $regArray));
        }
    }

    function updateProfile_post() {
        $this->config->load('rest', TRUE);
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        $email = $this->post('email');
        $profiledata = array(
            'name' => $this->post('name'),
            'email' => $this->post('email'),
            'contact_no' => $this->post('mobile_no'),
            'company' => $this->post('company'),
            'designation' => $this->post('designation'),
        );
        $this->db->set($profiledata);
        $this->db->where('email', $email); //set column_name and value in which row need to update
        $this->db->update("app_user");
        $this->db->order_by('name asc');

        $this->db->where('email', $email); //set column_name and value in which row need to update
        $query = $this->db->get('app_user');
        $userData = $query->row();
        $this->response(array("userdata" => $userData));
    }

    function saveCards_post() {
        $this->config->load('rest', TRUE);
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        $profiledata = array(
            'user_id' => $this->post('user_id'),
            'scanner_id' => $this->post('scanner_id'),
            'card_link' => "",
            'datetime' => date("Y-m-d H:i:s a"),
        );
        $scanner_id = $this->post('scanner_id');
        $user_id = $this->post('user_id');
        $this->db->where('user_id', $user_id);
        $this->db->where('scanner_id', $scanner_id);
        $query = $this->db->get('card_share');
        $userdata = $query->row();
        if ($userdata) {
            $this->response(array("msg" => "Card Already Saved."));
        } else {
            $this->db->insert('card_share', $profiledata);
            $this->response(array("msg" => "Card Has Been Saved."));
        }
    }

    function getUsersCard_get($user_id) {
        $this->db->where('user_id', $user_id);
        $query = $this->db->get('card_share');
        $userdata = $query->result_array();
        $usercarddata = [];
        foreach ($userdata as $key => $value) {
            $this->db->where('usercode', $value['scanner_id']);
            $query = $this->db->get('app_user');
            $user = $query->row();
            $user->cardid = $value['id'];
            $user->cardimage = base_url() . "assets/usercard/" . $user->cardimage;
            array_push($usercarddata, $user);
        }
        return $this->response($usercarddata);
    }

    function removeUsersCard_get($cardid) {
        $this->db->where('id', $cardid);
        $this->db->delete('card_share');
    }

    function createPost_post() {
        $this->config->load('rest', TRUE);
        $class_assignment = array(
            'title' => "",
            'description' => $this->post('description'),
            "datetime" => date("Y-m-d H:i:s a"),
            'user_id' => $this->post('user_id'),
        );
        $this->db->insert('post', $class_assignment);
        $last_id = $this->db->insert_id();
        $this->response(array("last_id" => $last_id));
    }

    function uploadFileImage_post() {
        $this->config->load('rest', TRUE);
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        $config['upload_path'] = 'assets/postfiles';
        $config['allowed_types'] = '*';
        $tableid = $this->post('file_table_id');
        $tempfilename = rand(10000, 1000000);
        $tempfilename = "" . $tempfilename . $tableid;

        $file_newname = $tempfilename . '.jpg';
        $config['file_name'] = $file_newname;
        //Load upload library and initialize configuration
        $this->load->library('upload', $config);
        $this->upload->initialize($config);
        if ($this->upload->do_upload('file')) {
            $uploadData = $this->upload->data();
            $tableid = $this->post('file_table_id');
            $file_newname = $uploadData['file_name'];
            $tablename = $this->post('file_tablename');
            $filecreate = array(
                'table_name' => $tablename,
                'table_id' => $this->post('file_table_id'),
                "file_name" => $file_newname,
                'file_real_name' => $this->post('name'),
                'file_type' => "jpg",
                "date" => date("Y-m-d"),
                "time" => date("H:i:s a"),
            );
            $this->db->insert('post_files', $filecreate);
            $this->response($file_newname);
        }
    }

    function postDelete_get($pid) {
        $this->db->where('id', $pid);
        $this->db->delete('post');


        $this->db->where('table_id', $pid);
        $this->db->delete('post_files');
    }

    function getPostData_get() {
        $imagepath = base_url() . "assets/postfiles/";
        $this->db->order_by("id desc");
        $query = $this->db->get('post');
        $postdata = $query->result();
        $postdataarray = array();
        foreach ($postdata as $key => $value) {
            $this->db->where("table_name", "post");
            $this->db->where("table_id", $value->id);
            $this->db->order_by("id desc");
            $query = $this->db->get('post_files');
            $images = $query->result();
            $postimages = array();
            foreach ($images as $key2 => $value2) {
                $temp = array(
                    "img" => $imagepath . $value2->file_name,
                    "index" => $key2,
                    "id" => $value2->id,
                );
                array_push($postimages, $temp);
            }
            $value->images = $postimages;
            array_push($postdataarray, $value);
        }
        $this->response($postdataarray);
    }

    function getGallaryDelete_get($imgid) {
        $this->db->where('id', $imgid);
        $this->db->delete('post_files');
    }

    function getGallary_get() {
        $imagepath = base_url() . "assets/postfiles/";
        $this->db->where("table_name", "gallery");
        $this->db->order_by("id desc");
        $query = $this->db->get('post_files');
        $gallaryImages = $query->result();
        $img1 = array();
        $img2 = array();
        $img3 = array();
        $albumData = array();
        foreach ($gallaryImages as $key => $value) {
            $temp = array(
                "img" => $imagepath . $value->file_name,
                "index" => $key,
                "id" => $value->id,
            );
            array_push($img2, $temp);
        }
        $this->response($img2);
    }

    function getUserPoints($userid) {
        $this->db->where('user_id', $userid);
        $this->db->order_by("id desc");
        $query = $this->db->get('points');
        $userpointdata = $query->result_array();
        $creditList = [];
        $debititList = [];
        $creditsum = 0;
        $debitsum = 0;
        foreach ($userpointdata as $pkey => $pvalue) {
            if ($pvalue['point_type'] == "Credit") {
                array_push($creditList, $pvalue);
                $creditsum += $pvalue["points"];
            } else {
                $debitsum += $pvalue["points"];
                array_push($debititList, $pvalue);
            }
        }
        return array("pointlist" => $userpointdata, "credit" => $creditsum, "debitsum" => $debitsum, "totalremain" => ($creditsum - $debitsum));
    }

    function getUserByMobCod_get($userinput) {
        $this->db->where('contact_no', $userinput);
        $this->db->or_where('usercode', $userinput);
        $query = $this->db->get('app_user');
        $userdata = $query->row();
        if ($userdata) {
            $userpoints = $this->getUserPoints($userdata->id);
        }
        else{
            $userpoints = [];
        }
        $this->response(array("userpoints" => $userpoints, "userdata" => $userdata));
    }

    function createPoints_post() {
        $this->config->load('rest', TRUE);
        $class_assignment = array(
            'points' => $this->post('points'),
            'description' => $this->post('description'),
            "datetime" => date("Y-m-d H:i:s a"),
            'user_id' => $this->post('user_id'),
            "point_type" => $this->post('point_type'),
        );
        $this->db->insert('points', $class_assignment);
        $last_id = $this->db->insert_id();
        $this->response(array("last_id" => $last_id));
    }

    function deletePoints_get($pointid) {
        $this->db->where('id', $pointid);
        $this->db->delete('points');
    }

}

?>