<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Question;
use App\Users;
use App\UserType;
use App\Article;
use App\Deals;
use App\Doctor;
use App\Disease;
use App\SelectQuestion;
use App\SelectQuestionSubject;
use App\Review;
use App\Clinic;
use App\Comment;
use App\Medicine;
use App\Answer;
use App\DoctorSpeciality;
use App\ClinicSpeciality;
use App\ClinicService;
use App\Catalog;
use App\DoctorService;
use App\Province;
use App\Speciality;
use App\District;
use App\Calltime;
use App\Social;
use App\Ads;
use Auth;
use Socialite;
use Session;
use Illuminate\Support\MessageBag;

class HomeController extends Controller
{
	public function __construct()
	{
        if(isset(Session::get('user')->user_id)){
            if (!isset($_SESSION))
            {
                session_start();
            }

            //session_start();
            $_SESSION['userid_chat'] = Session::get('user')->user_id;

        }
		$article = Article::orderBy('article_id','DESC')->limit(5)->get();
		view()->share('article',$article);
	
		$deals= Deals::orderBy('ranked','DESC')->get();
		view()->share('deals',$deals);

        //news new 5
		$news_new = Article::orderBy('article_id','DESC')->limit(5)->get();
        view()->share('news_new',$news_new);
        //news popular
        $news_popular = Article::where('popular', 1)->orderBy('article_id','DESC')->limit(5)->get();
        view()->share('news_popular',$news_popular);
	}
	public static function getProvinceID($province){
		$prov= \App\Province::where('province_name','like','%'.$province.'%')->firstOrFail();
		if($prov)
			return $prov->province_id;
		return false;
	}

    public function index(){
        $clinic = Clinic::where('featured','1')->limit(12)->get();
    	$doctor = Doctor::where('featured','1')->orderBy('doctor_name','DESC')->limit(9)->get();
    	$questions = SelectQuestionSubject::where('featured','1')->limit(12)->get();
    	$reviews = Review::all()->take(5);
    	$specialities = \App\Speciality::all();
        view()->share('clinic',$clinic);
    	view()->share('doctors',$doctor);
    	view()->share('specialities',$specialities);
    	view()->share('questions',$questions);
    	view()->share('reviews',$reviews);
    	return view('home');
    }
    public function aboutUs()
    {
        return view('bacsiviet/aboutUs');
    }
    public function recruitment()
    {
        return view('bacsiviet/recruitment');
    }
    public function contactUs()
    {
        return view('bacsiviet/contactUs');
    }
    public function disputeResolution()
    {
        return view('bacsiviet/disputeResolution');
    }
    public function informationSecurityCustomer()
    {
        return view('bacsiviet/informationSecurityCustomer');
    }
    public function resetPassword()
    {
        return view('taikhoan/resetPassword');
    }

    public function construction()
    {
        return view('dangxaydung/dangxaydung');
    }

    public function userGuide()
    {
        return view('huongdansudung/user');
    }
    public function professionalGuide()
    {
        return view('huongdansudung/placeAndProfessional');
    }
    public function placeGuide()
    {
        return view('huongdansudung/placeAndProfessional');
    }
    public function voucher()
    {
        return view('voucher/index');
    }



    public function timkiem(Request $rq){
    	if($rq->input('province')){
    		$provin = $rq->input('province');
    		$rq->session()->put('province',$provin);
    		$province = \App\Province::where('province_name','like','%'.$provin.'%')->first();
    		// if($province!=null){
    		// 	$rq->session()->put('province_id',$province->province_id);
    		// 	return redirect('/danh-sach');
    		// }
    		
    	}
    	if($rq->input('q')!=null){
    			$q = urldecode($rq->input('q'));
    			$benh = Disease::where('disease_name','like','%'.$q.'%');
    			$benh_count = $benh->count();
    			$benh = $benh->paginate(30);
	   			$thuoc = Medicine::where('description','like','%'.$q.'%')->count();
	   			$bs = Doctor::where('doctor_name','like','%'.$q.'%')->count();
	   			$csyt = Clinic::where('clinic_name','like','%'.$q.'%')->count();
	   			$qs = Question::where('question_title','like','%'.$q.'%')->count();
	   			$service = \App\Service::where('service_name','like','%'.$q.'%')->count();
    			return view('tim_kiem',['count'=>$benh_count,'benh'=>$benh,'thuoc'=>$thuoc,'doctor'=>$bs,'clinic'=>$csyt,'question'=>$qs,'service'=>$service]);
    	}else{
            echo '<script>alert("Vui lòng nhập từ khóa tìm kiếm.");window.history.back();</script>'; 
        }    	
    }
    public function hoibacsi_tuyenchon($id){
    	$ids= explode('-',$id);
    	$qid = $ids[count($ids)-1];
    	$tuyenchon = \App\SelectQuestionSubject::where('id',$qid)->first();
    	$qids=  \App\SelectQuestion::where('subject_id',$qid)->pluck('question_id')->all();
    	$questions = Question::whereIn('question_id',$qids)->get();
    	//var_dump($questions);
    	$subjects= SelectQuestionSubject::whereNotIn('subject',$ids)->take(6)->get();
    	return view('tuyenchon-detail',['questions'=>$questions,'subject'=>$tuyenchon,'subjects'=>$subjects]);
    }
    public function hoibacsi(){
    	$question = Question::orderBy('question_id','DESC')->paginate(10);
    	//var_dump($question);
    	$selectQuestion = SelectQuestionSubject::orderBy('created_at','DESC')->take(6)->get();
    	//var_dump($question->answers);
        $speciality = \App\Speciality::get();
        //var_dump($speciality);
 
        //var_dump($questions[0]->question_id);
        view()->share('speciality',$speciality);
    	return view('hoibacsi',['question' => $question,'selectquestion'=>$selectQuestion])->withPost($question);
    }
    public function hoibacsiPost(Request $rq){
        $title = $rq->title;
        $body = $rq->body;            
        $specialities = $rq->specialities;   
        if($rq->name != NULL){
            $name = $rq->name;
        }else{
            $name = "Giấu tên";
        }     
        
        $email = $rq->email;
        $user_id = $rq->user_id;

        if($title === null || $body === null || $email === null){
            $errors = new MessageBag(['errorMs' => 'Vui lòng điền vào các trường có dấu *']);
            return redirect()->back()->withInput()->withErrors($errors);
        }else{
            $question = new Question;
            $question->topic_id = $specialities;
            $question->user_id = $user_id;
            $question->fullname = $name;
            $question->question_title = $title;
            $question->question_content = $body;
            $question->question_url = $this->to_slug($title); 
            $question->speciality_id = $specialities; 
            $question->save();                       
            return redirect('/hoi-bac-si');
        }
    }


    public function hoibacsiview(Request $rq, $id){

    	// var_dump($id);die;
    	switch($id){
    		case "tuyen-chon":
    			$subjects = SelectQuestionSubject::orderBy('created_at')->paginate(30);
    			return view('hoibacsi-tuyenchon',['subjects'=>$subjects])->withPost($subjects);
    			break;
    		case "dat-cau-hoi":
    			$specialities = \App\Speciality::all();
    			view()->share('specialities',$specialities);
    			return view('datcauhoi');
    			break;
    		case "danh-sach":
    			$unanswered = $rq->input('unanswered');
    			//var_dump($unanswered);
    			$all = \App\Answer::pluck('question_id')->all();
    			if($unanswered==="true"){
    				
    				$questions = \App\Question::whereNotIn('question_id',$all)->select('*')->paginate(20);
    				//var_dump($questions);
    			}
    			else{
    				$questions = \App\Question::whereIn('question_id',$all)->select('*')->paginate(20);
    			}
    			//$questions = \App\Answer::all();
    			///view()->share('questions',$questions);
    			//$question = Question::orderBy('question_id','DESC')->paginate(10);
    			if($rq->input('q')!=null){
    				$q = urldecode($rq->input('q'));
    				$benh = Disease::where('disease_name','like','%'.$q.'%');
    				$benh_count = $benh->count();
    				$benh = $benh->paginate(30);
    				$thuoc = Medicine::where('description','like','%'.$q.'%')->count();
    				$bs = Doctor::where('doctor_name','like','%'.$q.'%')->count();
    				$csyt = Clinic::where('clinic_name','like','%'.$q.'%')->count();
    				$qs = Question::where('question_title','like','%'.$q.'%')->count();
    				$questions = Question::where('question_title','like','%'.$q.'%')->paginate(30);
    				$service = \App\Service::where('service_name','like','%'.$q.'%')->count();
    				return view('hoibacsi_danhsach',['count'=>$benh_count,'questions'=>$questions,'thuoc'=>$thuoc,'doctor'=>$bs,'clinic'=>$csyt,'question'=>$qs,'service'=>$service])->withPost($questions);
    			}
    			return view('hoibacsi_danhsach',['questions'=>$questions])->withPost($questions);
    			break;
    		default:
    			return $this->hoibacsi_showdetail($id);
    			break;
    	}
    
    }

    public function test(){
        return "fsda";
    }

    public function bacsitraloi(Request $rq, $id){
        // var_dump("fds");die;
        $info = $rq->get('reply_as_information');
        $infoParse = json_decode($info);
        $thred_id = $rq->get('thread_id');
        $body = $rq->get('body');

        // $question = Question::orderBy('question_id','DESC')->paginate(10);
        // var_dump(json_decode($info));die;
        if(!empty( $infoParse) && !empty($thred_id) && !empty($body)){
            $answers = new Answer;
            $answers->question_id = $rq->get('thread_id');
            $answers->answer_type = $infoParse->reply_as_type;
            $answers->answer_user_id = $infoParse->reply_as_id;
            $answers->answer_content = $rq->get('body');
            $answers->save();
        }

        return $this->hoibacsi_showdetail($id);
    }
    public function thuoc_danhsach(){
    	return view('thuoc_danhsach');
    }
    public function hoibacsi_danhsach()
    {
        return view('hoibacsi_danhsach');
    }

    public function hoibacsi_showdetail($id){

        $ads = Ads::where('place','1')->get();

    	$url = explode('-',$id);
    	$qid = $url[count($url)-1];
    	$question = Question::find($qid);
    	//$data[] = $question;
    	//var_dump($question);
    	//echo $qid;
    	return view('hoibacsiview',compact('ads'))->with('question',$question);
    }
    public function listbaiviet(){
    	$Catalog = Catalog::all();
        $baiviet_new=Article::orderBy('article_id','DESC')->limit(1)->first();
        $baiviets  =Article::orderBy('article_id','DESC')->limit(5)->get();
        return view('baiviet-list',['baiviets' => $baiviets,'Catalog' => $Catalog,'baiviet_new'=>$baiviet_new])->withPost($baiviets);
    }
    public function baivietdetail($id){
    	echo $id;
    }
    
    public function bacsi_danhsach(Request $rq){

        $ads = Ads::where('place','5')->get();

    	$doctors = Doctor::where('status','1')->orderBy('doctor_id','DESC');
    	if($rq->input('q')){
            if($rq->input('key') == 'specialities'){
                $speciality = Speciality::where('specialty_url',$rq->input('q'))->first();
                  $doctors = Doctor::Join('doctor_speciality','doctor.doctor_id', '=', 'doctor_speciality.doctor_id')->where('doctor_speciality.speciality_id',$speciality->speciality_id)->paginate(30);
                $bs = Doctor::Join('doctor_speciality','doctor.doctor_id', '=', 'doctor_speciality.doctor_id')->where('speciality_id',$speciality->speciality_id)->count();
                $q = urldecode($rq->input('q'));
               $user = Users::where('addpress', $rq->input('q'))->get();
               $qs = Question::where('question_title','like','%'.$q.'%')->count();
                $service = \App\Service::where('service_name','like','%'.$q.'%')->count();

                return view('danhsach_bacsi',['doctors'=>$doctors,'doctor'=>$bs,'question'=>$qs,'service'=>$service,'ads'=>$ads, 'user'=>$user, 'speciality' => $speciality])->withPost($doctors);
                
            }
            else if($rq->input('key') == 'city'){
            // $doctors = Doctor::Join('user', 'doctor.user_id', '=', 'user.user_id')
            // ->where('user.addpress',$rq->input('q'))->paginate(30);

                $doctors = Doctor::where('doctor_address','like',$rq->input('q'))->paginate(30);
           
               $q = urldecode($rq->input('q'));
               $user = Users::where('addpress', $rq->input('q'))->get();
                //$doctors = Doctor::where('user_id','like','%trung%')->paginate(30);
                 
               $bs = Doctor::where('doctor_address','like',$rq->input('q'))->count();

            //     $bs = Doctor::Join('user', 'doctor.user_id', '=', 'user.user_id')
            // ->where('user.addpress',$rq->input('q'))->count();
               
                $qs = Question::where('question_title','like','%'.$q.'%')->count();
                $service = \App\Service::where('service_name','like','%'.$q.'%')->count();
                return view('danhsach_bacsi',['doctors'=>$doctors,'doctor'=>$bs,'question'=>$qs,'service'=>$service,'ads'=>$ads, 'user'=>$user])->withPost($doctors);
                }
            else if($rq->input('key') == 'clinic'){
                echo "clinic";
                die();
            }
    		$q = urldecode($rq->input('q'));
    		$doctors = Doctor::where('doctor_name','like','%'.$q.'%')->paginate(30);
	    	
	    	$benh = Disease::where('disease_name','like','%'.$q.'%');
	    	$benh_count = $benh->count();
	    	//$benh = $benh->paginate(30);
	    	$thuoc = Medicine::where('description','like','%'.$q.'%')->count();
	    	$bs = Doctor::where('doctor_name','like','%'.$q.'%')->count();
	    	$csyt = Clinic::where('clinic_name','like','%'.$q.'%')->count();
	    	$qs = Question::where('question_title','like','%'.$q.'%')->count();
	    	$service = \App\Service::where('service_name','like','%'.$q.'%')->count();
	    	return view('danhsach_bacsi',['doctors'=>$doctors,'count'=>$benh_count,'thuoc'=>$thuoc,'doctor'=>$bs,'clinic'=>$csyt,'question'=>$qs,'service'=>$service,'ads'=>$ads])->withPost($doctors);
    	}

    	if($rq->input('province')!=null || $rq->input('speciality')!=null){
    		$prv= Province::where('province_name',$rq->input('province'))->first();
    		$sp= Speciality::where('specialty_url',$rq->input('speciality'))->first();
    		if($prv!=null){

    			//$doctors= $doctors->where('doctor_address',$prv->province_url);
                //$doctors= Users::where('addpress',$prv->province_url);
                //$doctors=$doctors->paginate(30);

            //     $doctors = Doctor::Join('user', 'doctor.user_id', '=', 'user.user_id')
            // ->where('user.addpress',$prv->province_name);

                $doctors = Doctor::where('doctor_address','like',$rq->input('province'));
                
    		}
    		if($sp!=null){
    			$specialities = \App\DoctorSpeciality::where('speciality_id',$sp->speciality_id)->pluck('doctor_id')->all();
    			$doctors=  $doctors->whereIn('doctor_id',$specialities);
    			//echo count($doctors); return;
    		}
    		//
    	
    	}
        
    	$doctors=$doctors->paginate(30);
    	return view('danhsach_bacsi',['doctors'=>$doctors,'ads'=>$ads])->withPost($doctors);
    }
    
    public function danhsach_csyt(Request $rq){
        $ads = Ads::where('place','6')->get();
    	$baiviets  =Article::where('catalog_id','14')->orderBy('article_id','DESC')->limit(10)->get();
    	$clinics = Clinic::orderBy('clinic_id','DESC'); 
    	if(isset($_COOKIE['@province']) && $_COOKIE['@province']!=""){
    		$clinics = Clinic::where('province_id',$this->getProvinceID($_COOKIE['@province']))->orderBy('clinic_id','DESC');
    	}
    	if($rq->input('provinces')!=null){
    		
    	}
    	if($rq->input('specialities')!=null){
    		$speciality = \App\Speciality::where('specialty_url',$rq->input('specialities'))->first();

    		if($speciality!=null){
    			//echo 'test';return;
    			$clinic_ids = \App\ClinicSpeciality::where('speciality_id',$speciality->speciality_id)->pluck('clinic_id')->all();    			

    			$clinics = Clinic::whereIn('clinic_id',$clinic_ids)->orderBy('clinic_id','DESC'); 			

    		}
    		//var_dump($clinicss);
    	}
    	if($rq->input('place_types')!=null){
    		$speciality = \App\Speciality::where('specialty_url',$rq->input('place_types'))->firstOrFail();
    		if($speciality!=null){
    			$clinic_ids = \App\ClinicSpeciality::where('speciality_id',$speciality->speciality_id)->pluck('clinic_id')->all();
    			$clinics = Clinic::whereIn('clinic_id',$clinic_ids)->orderBy('clinic_id','DESC')->paginate(20);
    		}
    	}
    	if($rq->input('q')){
    		//echo $rq->input('q');return;
    		$clinics = Clinic::where('clinic_name','like','%'.$rq->input('q').'%')->orderBy('clinic_id','DESC')->paginate(30);
    		$q = urldecode($rq->input('q'));
    			$benh = Disease::where('disease_name','like','%'.$q.'%');
    			$benh_count = $benh->count();
    			//$benh = $benh->paginate(30);
	   			$thuoc = Medicine::where('description','like','%'.$q.'%')->count();
	   			$bs = Doctor::where('doctor_name','like','%'.$q.'%')->count();
	   			$csyt = Clinic::where('clinic_name','like','%'.$q.'%')->count();
	   			$qs = Question::where('question_title','like','%'.$q.'%')->count();
	   			$service = \App\Service::where('service_name','like','%'.$q.'%')->count();
    		//s echo count($clinics);return;
	   			return view('danhsach_csyt',['clinics'=>$clinics,'count'=>$benh_count,'thuoc'=>$thuoc,'doctor'=>$bs,'clinic'=>$csyt,'question'=>$qs,'service'=>$service])->withPost($clinics);
	   			 
    	}
    	if($rq->input('province')!=null || $rq->input('district')!=null || $rq->input('speciality')!=null){
    		$prv= Province::where('province_url',$rq->input('province'))->first();
    		$dis= District::where('url',$rq->input('district'))->first();
    		$sp= Speciality::where('specialty_url',$rq->input('speciality'))->first();
    		if($prv!=null){
    			$clinics = $clinics->where('province_id',$prv->province_id);
    		}
    		if($dis!=null){
    			$clinics = $clinics->where('district_id',$dis->id);
    		}
    		if($sp!=null){
    			$clinic_ids = \App\ClinicSpeciality::where('speciality_id',$sp->speciality_id)->pluck('clinic_id')->all();
    			$clinics = $clinics->whereIn('clinic_id',$clinic_ids)->orderBy('clinic_id','DESC');
    		}
    	}
    	$clinics= $clinics->paginate(30);
    	//var_dump($clinics[0]->specialities[0]->clinic);
    	//view()->share('clinics',$clinics);
    	return view('danhsach_csyt',['baiviets'=>$baiviets],['clinics'=>$clinics,'ads'=>$ads])->withPost($clinics);
    }
    public function chitiet_csyt($id){
    	if($id=='danh-sach'){
    		$clinics = Clinic::all();
    		//var_dump($clinics[0]->specialities[0]->clinic);
    		view()->share('clinics',$clinics);
    		return view('danhsach_csyt');
    		
    	}
    	
    	$url = explode('-',$id);
    	$uid =$url[count($url)-1];
    	
    	$comment = Comment::where('clinic_id',$uid)->orderBy('created_at', 'DESC')->get();
    	$danhgia = Comment::where('feedback_', '>', 0)->count('feedback_');
    	$sum = Comment::sum('feedback_');
    	$csyt = \App\Clinic::find($uid);
    	$chuyenkhoa = \App\ClinicSpeciality::where('clinic_id',$id)->get();
    	$bacsi = \App\DoctorClinic::where('clinic_id',$uid)->get();
    	
    	return view('chitiet_csyt',['cs'=>$csyt,'bacsi'=>$bacsi,'comment'=>$comment,'danhgia'=>$danhgia,'sum'=>$sum]);
    }
    public function chuyenkhoa(Request $rq){
    	$specialities = \App\Speciality::paginate(30);
    	view()->share('specialities',$specialities);
    	return view('chuyenkhoa')->withPost($specialities);
    }
    public function chuyenkhoadetail($id){
    	$url = explode('-',$id);
    	 
    	$qid = $url[count($url)-1];
    	$speciality = \App\Speciality::find($qid);
    	//var_dump($speciality);
    	$questions = Question::where('speciality_id',$qid)->orderby('created_at','DESC')->get();
    	$docid= \App\DoctorSpeciality::where('speciality_id',$speciality->speciality_id)->pluck('doctor_id')->all();
    	//var_dump($questions[0]->question_id);
    	$clinicid= ClinicSpeciality::where('speciality_id',$speciality->speciality_id)->pluck('clinic_id')->all();
    	
    	$clinics = Clinic::whereIn('clinic_id',$clinicid)->take(10)->get();
    	//var_dump($clinics);return;
    	$doctors= Doctor::whereIn('doctor_id',$docid)->take(10)->get();
        view()->share('speciality',$speciality);
    	view()->share('questions',$questions);
    	return view('chuyenkhoadetail',['doctors'=>$doctors,'clinics'=>$clinics]);
    }
    public function khuyenmaidetail(Request $rq, $url){
    	$ids = explode('-',$url);
    	$id = $ids[count($ids)-1];
    	$khuyenmai = \App\Deals::where('deal_id',$id)->first();
    	$khuyenmai->ranked = $khuyenmai->ranked +1;
    	$khuyenmai->save();
    	$comment  = comment::where('deal_id',$id)->orderBy('created_at','DESC')->get();
    	return view('khuyenmai_detail',['deal'=>$khuyenmai,'comment'=>$comment]);
    }
    public function dealcomment(Request $rq){
    	$body = $rq->input('body');
    	$deal_id = $rq->input('deal_id');
    	$deal = Deals::find($deal_id);
    	$comment = new Comment;
    	$comment->user_id = $rq->session()->get('user')->user_id;
    	$comment->deal_id = $deal_id;
    	$comment->content = $body;
    	$comment->save();
    	return redirect('/khuyen-mai/'.Deals::strtoUrl($deal->deal_title).'-'.$deal_id);
    }
    public function bacsi_detail($id){
    	$url = explode('-',$id);
    	
    	$qid = $url[count($url)-1];
    	$doctor = Doctor::find($qid);
    	view()->share('doctor',$doctor);
    	$comment = Comment::where('doctor_id',$doctor->doctor_id)->orderBy('created_at', 'DESC')->get();
    	$like = Comment::where('doctor_id',$doctor->doctor_id)->where('liking','1')->get();
    	//var_dump($doctor->activity[0]->question);
    	$doctor_user = Users::find($doctor->user_id);
    	if($doctor_user!=null){
    	$answers = Answer::where('answer_user_id',$doctor_user->user_id)->count();
    	}
    	else{
    		$answers = 0;
    	}
    	return view('bacsi-detail',['comment'=>$comment,'like'=>$like,'answer'=>$answers]);
    }
    function postInfoDoctor(Request $rq){
        $doctor_id = $rq->id;
        $doctor = Doctor::find($doctor_id);

        if($doctor != null){
            $spec = \App\DoctorSpeciality::where('doctor_id',$doctor_id)->pluck('speciality_id')->first();
            $spec_url = \App\Speciality::where('speciality_id',$spec)->first();
            $link = "http://bacsiviet.vn/danh-sach/bac-si/".$doctor->doctor_url.'-'.$doctor_id.'/'.$spec_url->specialty_url;
            
            header('Content-Type: application/json; charset=utf-8');
                        return json_encode(array('isLogin' => true,'msg'=>'Thông Tin Bác Sĩ','link'=>$link),JSON_UNESCAPED_UNICODE);
        }
        else{
             header('Content-Type: application/json; charset=utf-8');
                return json_encode(array('isLogin' => false,'msg'=>'Không Tồn Tại Bác Sĩ Này!'),JSON_UNESCAPED_UNICODE);
        }
    }
    function listkhuyenmai(Request $rq){
        

            
            //  header('Content-Type: application/json; charset=utf-8');
            //  echo json_encode(array('a'=>"'bar'",'b'=>'"baz"','c'=>'&blong&', 'd'=>"ê"));

            // die();


        $deals= Deals::where('special_sale',1)->orderBy('ranked','DESC')->get();
        $listid = array();

        if($deals){
            foreach($deals as $dl){
                // array_push($listid,$dl);
                // $listid['link_detail'] = 'http://bacsiviet.vn/khuyen-mai/'.$this->to_slug($dl->deal_title).'-'.$dl->deal_id;

                //array_shift($listid);
                
                    echo json_encode(array('deal_id'=>$dl->deal_id,'deal_title'=>$dl->deal_title,'image'=>'http://bacsiviet.vn/public/images/'.$dl->image, 'deal_description'=>$dl->description,'deal_content'=>$dl->deal_content, 'link_detail'=>'http://bacsiviet.vn/khuyen-mai/'.$this->to_slug($dl->deal_title).'-'.$dl->deal_id),JSON_UNESCAPED_UNICODE),"\n";

                    // header('Content-Type: application/json; charset=utf-8');
                    //  echo json_encode(array('deal_id'=>$dl->deal_id,'deal_title'=>$dl->deal_title,'image'=>$dl->image, 'deal_description'=>$dl->description,'deal_content'=>$dl->deal_content, 'link_detail'=>'http://bacsiviet.vn/khuyen-mai/'.$this->to_slug($dl->deal_title).'-'.$dl->deal_id),JSON_UNESCAPED_UNICODE);


                // header('Content-Type: application/json; charset=utf-8');
                // echo  json_encode(array('deal_id'=>$dl->deal_id, 'deal_title'=>$dl->deal_title,'image'=>$dl->image, 'deal_description'=>$dl->description,'deal_content'=>$dl->deal_content, 'link_detail'=>'http://bacsiviet.vn/khuyen-mai/'.$this->to_slug($dl->deal_title).'-'.$dl->deal_id),JSON_UNESCAPED_UNICODE);


                // $jsons = json_encode($listid,JSON_UNESCAPED_UNICODE);
                // array_shift($listid);
                // array_shift($listid);
                // echo ($jsons)."\n\n";
            
            }
            
            
           // die();
            
        }else{
            header('Content-Type: application/json: charset=utf-8');
            return json_encode(array('isLogin' => false,'msg'=>'Không có khuyến mãi nào!'),JSON_UNESCAPED_UNICODE);
        }
    }

    function detailkhuyenmai(Request $rq){
        echo "detail";
        die();
    }

    public function logout(){
        session()->forget('user');
        session_destroy();
    }
    public function getdangky(){
        
        return view('dangky');
    }
    public function postDangky(Request $rq){

    	// echo $rq->type."<br/>";
    	// echo $rq->name."<br/>";
    	// echo $rq->mobile_phone."<br/>";
    	// echo $rq->email."<br/>";
    	// echo $rq->ngt."<br/>";
    	// $pass = Hash::make($rq->password);
    	// echo $pass."<br/>";

    	$email = $rq->email;
    	$phone = $rq->mobile_phone;
    	$name = $rq->name;
    	$password = $rq->password;
    	$type = $rq->type;
        $ngt = $rq->ngt;
        $type = 0;
        
    	if($email!=null && $name!=null && $password != null){
    		$user = Users::where('email',$email)->first();
    		if($user==null){
    			$user = new Users;  		
	    		$user->email = $email;
	    		$user->fullname= $name;
	    		$user->phone = $phone;
                $user->present = $ngt;
                $user->addpress = 'Việt Nam';
	    		$user->password= Hash::make($password);
	    		if($rq->type == "user"){
                    $type = 1;
                }
                else if($rq->type == "professional"){
                    $type = 2;
                }
                else if($rq->type == "place"){
                    $type = 3;
                }
                $user->user_type_id = $type;
                $user->paid = 1;
	    		$user->save();
                return view('register_success',compact("name",'email','phone'));
    		}else{
    			$errors = new MessageBag(['errorReg' => 'Username này đã có người sử dụng, vui lòng nhập username khác']);
                return redirect()->back()->withInput()->withErrors($errors);
    		}
    		
    	}else{
            $errors = new MessageBag(['errorReg' => 'Hộ Tên, Username và mật khẩu không được để trống.']);
            return redirect()->back()->withInput()->withErrors($errors);
        }    	
    }

    public function postDangkyApp(Request $rq){
        $username = $rq->username;
        $password = $rq->password;
        $fullname = $rq->fullname;
        $presenter = $rq->presenter;
        $phone = $rq->phone;
        $type = 1;
        
        if($username!=null && $fullname!=null && $password != null){

            $user = Users::where('email',$username)->first();
            if($user==null){
                
                $user = new Users;          
                $user->email = $username;
                $user->fullname= $fullname;
                $user->phone = $phone;
                $user->present = $presenter;
                $user->addpress = 'Việt Nam';
                $user->password= Hash::make($password);
                if($rq->type == "user"){
                    $type = 1;
                }
                else if($rq->type == "professional"){
                    $type = 2;
                }
                else if($rq->type == "place"){
                    $type = 3;
                }
                $user->user_type_id = $type;
                $user->paid = 1;
                if($user->save()){
                    if($user->user_type_id == 1){
                        $user_type = "user";
                    }
                    else if($user->user_type_id == 2){
                        $user_type = "doctor";
                        $doctor = new Doctor;
                        $doctor->doctor_name     = 'BS '.$user->fullname;
                        $doctor->doctor_url      = $this->to_slug('BS '.$user->fullname);      
                        $doctor->user_id         = $user->user_id;
                        $doctor->experience      = '<ul><li>20 năm  bệnh viện Chợ rẫy</li></ul>';
                        $doctor->training        = '<ul><li>Đại học y dược HCM</li></ul>';
                        $doctor->doctor_address  = 'Hồ Chí Minh';
                        $doctor->profile_image   = '246170446bacsi.jpg';
                        $doctor->doctor_timework = '7h đến 19h';
                        $doctor->doctor_clinic   = 'bv Đại Học Y Dược';
                        if($doctor->save()){
                            $docsp = new DoctorSpeciality;
                            $docsp->doctor_id = $doctor->doctor_id;
                            $docsp->speciality_id = 1;
                            $docsp->save();
                        
                        
                            $docser = new DoctorService;
                            $docser->doctor_id = $doctor->doctor_id;
                            $docser->service_id = 1;
                            $docser->save();
                        }
                        
                        //$doctor->profile_image = '246170446bacsi.jpg';
                    }
                    else if($user->user_type_id == 3){
                        $user_type = "clinic";
                    }
                    // header('Content-Type: application/json; charset=utf-8');
                    //     return json_encode(array('isLogin' => true,'msg'=>'Đăng Kí Thành Công','type'=>$user_type,'fullname'=>$user->fullname,'presenter'=>$user->present, 'phone'=>$user->phone,'username'=>$user->email,'password'=>$user->password),JSON_UNESCAPED_UNICODE);
                    header('Content-Type: application/json; charset=utf-8');
                        return json_encode(array('isLogin' => true,'msg'=>'Đăng Kí Thành Công','user_type'=>$user_type,'paid'=>$user->paid,'fullname'=>$user->fullname),JSON_UNESCAPED_UNICODE);
                }
            }
            else{
                header('Content-Type: application/json; charset=utf-8');
                return json_encode(array('isLogin' => false,'msg'=>'Tên Tài Khoản Đã Tồn Tại!'));
            }
        }else{
            header('Content-Type: application/json; charset=utf-8');
            return json_encode(array('isLogin' => false,'msg'=>'Chưa Nhập Đầy Đủ Thông Tin!'));
        }
    }


    public function dangnhap(Request $rq){
    	if($rq->input('next')!=null){
    		$rq->session()->put('next',$rq->input('next'));
    	}
    	if($rq->session()->has('user')){
    		if($rq->input('next')!=null)
    			return redirect($rq->input('next'));
    		return redirect('/tai-khoan');
    	}else{
    		return view('dangnhap');
    	}
    }
    public function testMobile(Request $rq)
    {
        $dt = new \DateTime();
        $Y = (int)date_format($dt,'Y');
        $m = (int)date_format($dt,'m');
        $d = (int)date_format($dt,'d');
        $date_ser = date_format($dt,'Y-m-d');
        $email = $rq->get('email'); 
        $pass = $rq->get('pwd');
        $pass = "$pass";

        if(!empty($email) && !empty($pass)) 
        { 
            $user = Users::where('email',$email)->first();
            if($user!=null){
                $check_time_use_of_user = Users::select('use_date')->where('user_id',$user->user_id)->first()->use_date;
            
                if($date_ser <= $check_time_use_of_user){
                    if(Hash::check($pass,$user->password)){
                        $user_type='';
                        $fullname = '';
                        $image = '';
                        if($user->user_type_id == 1){
                            $user_type = 'user';
                            //$fullname .= $email ."-";
                            $fullname .= $user->fullname ."-";
                            $fullname .= $user->sex==1?'Nam':'Nữ';
                            $image = $user->avatar;
                            $fullname .= "," .$user->addpress ;
                        }else if($user->user_type_id == 2){

                            $user_type = 'doctor';
                            //$urls = "http://bacsiviet/public/images/doctor/";
                            $urls = $_SERVER['HTTP_HOST']."/public/images/doctor/";
                            $doctorid = $user->doctor->doctor_id;
                            $image = $urls.$user->doctor->profile_image;
                            $spec = \App\DoctorSpeciality::where('doctor_id',$doctorid)->pluck('speciality_id')->all();
                            $speciality = Speciality::select('speciality_name')->whereIn('speciality_id',$spec)->get();
                            $fullname .= $user->doctor->doctor_name ."-";
                            $speciality_str = '';
                            foreach ($speciality as $key => $value) {

                                $speciality_str .= $value['speciality_name'] . ",";
                            }
                            $fullname .= $speciality_str;

                            $fullname = rtrim($fullname, ",");
                                
                        }else if($user->user_type_id == 3){
                            $user_type = 'clinic';
                            $clinicid = $user->clinic->clinic_id;
                            $cli = \App\ClinicSpeciality::where('clinic_id',$clinicid)->pluck('speciality_id')->all();
                            $speciality = Speciality::select('speciality_name')->whereIn('speciality_id',$cli)->get();
                            
                            $fullname .= $user->clinic->clinic_name ."-";
                            $speciality_str = '';
                            foreach ($speciality as $key => $value) {

                                $speciality_str .= $value['speciality_name'] . ",";
                                
                            }
                            $fullname .= $speciality_str;
                            $fullname = rtrim($fullname, ",");
                        }else{
                            $user_type = 'undefined';
                        }
                        header('Content-Type: application/json; charset=utf-8');
                        return json_encode(array('isLogin' => true,'msg'=>'Login Success','user_type'=>$user_type,'image'=>$image,'paid'=>$user->paid,'fullname'=>$fullname),JSON_UNESCAPED_UNICODE);
                    }else{
                        return json_encode(array('isLogin' => false,'msg'=>'Incorrect password')); 
                    } 
                }
                  
                return json_encode(array('isLogin' => false,'msg'=>'Account has expired'));
            }else{
                return json_encode(array('isLogin' => false,'msg'=>'Account does not exist')); 
            }
        }
    }
	
	public function loginface(Request $rq){
        $username = $rq->get('username');
        $id = $rq->get('qwd');

        $user_type = "";
        $u = Users::where('id_facebook',$id)->first();

        if($u){
            

            $rq->session()->put('user',$u);
            if($u->user_type_id == 1){
                $user_type = "user";
            }
            else if($u->user_type_id == 2){
                $user_type = "doctor";
            }
            else if($u->user_type_id == 3){
                $user_type = "clinic";
            }
            header('Content-Type: application/json; charset=utf-8');
            return json_encode(array('isLogin' => true,'msg'=>'Login Success','user_type'=>$user_type,'image'=>$u->avatar,'paid'=>$u->paid),JSON_UNESCAPED_UNICODE);

        }
        else{

            $u = new Users;
            //$u->fullname = $user->name;
            $u->email = $username;
            $u->id_facebook = $id;
            $u->user_type_id = 1;
            $u->avatar = "";
            $u->paid = 1;
            
            
            if($u->save()){
                if($u->user_type_id == 1){
                    $user_type = "user";
                }
                else if($u->user_type_id == 2){
                    $user_type = "doctor";
                }
                else if($u->user_type_id == 3){
                    $user_type = "clinic";
                }
                header('Content-Type: application/json; charset=utf-8');
                    return json_encode(array('isLogin' => true,'msg'=>'Login Success','user_type'=>$user_type,'image'=>$u->avatar,'paid'=>$u->paid),JSON_UNESCAPED_UNICODE);
            }
        }
        
    }
    public function LoginFacebookMobile(Request $rq)
    {
        $dt = new \DateTime();
        $Y = (int)date_format($dt,'Y');
        $m = (int)date_format($dt,'m');
        $d = (int)date_format($dt,'d');
        $date_ser = date_format($dt,'Y-m-d');
        $email = $rq->get('username'); 
        $pass = $rq->get('pwd');
        $pass = "$pass";
        
        if(!empty($email) && !empty($pass)) 
        { 
            $user = Users::where('email',$email)->first();
            if($user!=null){
                $check_time_use_of_user = Users::select('use_date')->where('user_id',$user->user_id)->first()->use_date;
            
                if($date_ser <= $check_time_use_of_user){
                    if(Hash::check($pass,$user->password)){
                        $user_type='';
                        $fullname = '';
                        $image = '';
                        if($user->user_type_id == 1){
                            $user_type = 'user';
                            $fullname .= $email ."-";
                            //$fullname .= $user->fullname ."-";
                            $fullname .= $user->sex==1?'Nam':'Nữ';
                            $image = $user->avatar;
                            $fullname .= "," .$user->addpress ;
                        }else if($user->user_type_id == 2){

                            $user_type = 'doctor';
                            //$urls = "http://bacsiviet/public/images/doctor/";
                            //$urls = $_SERVER['HTTP_HOST']."/public/images/doctor/";
                            $urls = $_SERVER['HTTP_HOST']."/";
                            $doctorid = $user->doctor->doctor_id;
                            //$image = $urls.$user->doctor->profile_image;
                            $image = $urls.$user->doctor->profile_image;
                            $spec = \App\DoctorSpeciality::where('doctor_id',$doctorid)->pluck('speciality_id')->all();
                            $speciality = Speciality::select('speciality_name')->whereIn('speciality_id',$spec)->get();
                            $fullname .= $user->doctor->doctor_name ."-";
                            $speciality_str = '';
                            foreach ($speciality as $key => $value) {

                                $speciality_str .= $value['speciality_name'] . ",";
                            }
                            $fullname .= $speciality_str;

                            $fullname = rtrim($fullname, ",");
                                
                        }else if($user->user_type_id == 3){
                            $user_type = 'clinic';
                            $clinicid = $user->clinic->clinic_id;
                            $cli = \App\ClinicSpeciality::where('clinic_id',$clinicid)->pluck('speciality_id')->all();
                            $speciality = Speciality::select('speciality_name')->whereIn('speciality_id',$cli)->get();
                            
                            $fullname .= $user->clinic->clinic_name ."-";
                            $speciality_str = '';
                            foreach ($speciality as $key => $value) {

                                $speciality_str .= $value['speciality_name'] . ",";
                                
                            }
                            $fullname .= $speciality_str;
                            $fullname = rtrim($fullname, ",");
                        }else{
                            $user_type = 'undefined';
                        }
                        header('Content-Type: application/json; charset=utf-8');
                        return json_encode(array('isLogin' => true,'msg'=>'Login Success','user_type'=>$user_type,'image'=>$image,'paid'=>$user->paid,'fullname'=>$fullname),JSON_UNESCAPED_UNICODE);
                    }else{
                        return json_encode(array('isLogin' => false,'msg'=>'Incorrect password')); 
                    } 
                }
                  
                return json_encode(array('isLogin' => false,'msg'=>'Account has expired'));
            }else{
                return json_encode(array('isLogin' => false,'msg'=>'Account does not exist')); 
            }
        }
    }
    public function postDangNhapMobile(Request $rq)
    {
        $dt = new \DateTime();
        $Y = (int)date_format($dt,'Y');
        $m = (int)date_format($dt,'m');
        $d = (int)date_format($dt,'d');
        $date_ser = date_format($dt,'Y-m-d');
        $email = $rq->get('email'); 
        $pass = $rq->get('pwd');
        $pass = "$pass";

        if(!empty($email) && !empty($pass)) 
        { 
            $user = Users::where('email',$email)->first();
            if($user!=null){
                $check_time_use_of_user = Users::select('use_date')->where('user_id',$user->user_id)->first()->use_date;
            
                //if($date_ser <= $check_time_use_of_user){
                    if(Hash::check($pass,$user->password)){
                        $user_type='';
                        $fullname = '';
                        $link = '';
                        $id = 0;
                        if($user->user_type_id == 1){
                            $user_type = 'user';
                            $fullname = $user->fullname;
                            
                        }else if($user->user_type_id == 2){
                            $user_type = 'doctor';
                            // $userid = $user->doctor->user_id;
                            // echo $userid;
                            // die();
                            $doctorid = $user->doctor->doctor_id;
                            // echo $doctorid;
                            // die();
                             if($doctorid){
                                $doctorname = \App\Doctor::where('doctor_id',$doctorid)->first();
                                // echo $doctorname->doctor_url;
                                // die();
                                $spec = \App\DoctorSpeciality::where('doctor_id',$doctorid)->pluck('speciality_id')->first();
                                $spec_url = \App\Speciality::where('speciality_id',$spec)->first();
                                // print_r($spec_url->specialty_url);
                                // die();
                                $fullname = $doctorname->doctor_name;
                                //$link = $doctorname->doctor_url.'-'.$doctorid.'/'.$spec_url->specialty_url.'';
                                $id = $doctorid;
                            }
                            // $doctorid = $user->doctor->doctor_id;
                            // $spec = \App\DoctorSpeciality::where('doctor_id',$doctorid)->pluck('speciality_id')->all();
                            // $speciality = Speciality::select('speciality_name')->whereIn('speciality_id',$spec)->get();
                            // $fullname .= $user->doctor->doctor_name ."-";
                            // $speciality_str = '';
                            // foreach ($speciality as $key => $value) {
                            //     $speciality_str .= $value['speciality_name'] . ",";
                            // }
                            // $fullname .= $speciality_str;
                            // $fullname = rtrim($fullname, ",");
                            else{
                                $fullname = $user->fullname;
                                
                            }
                            
                                
                        }else if($user->user_type_id == 3){
                            $user_type = 'clinic';
                            // $clinicid = $user->clinic->clinic_id;
                            // $cli = \App\ClinicSpeciality::where('clinic_id',$clinicid)->pluck('speciality_id')->all();
                            // $speciality = Speciality::select('speciality_name')->whereIn('speciality_id',$cli)->get();
                            
                            // $fullname .= $user->clinic->clinic_name ."-";
                            // $speciality_str = '';
                            // foreach ($speciality as $key => $value) {

                            //     $speciality_str .= $value['speciality_name'] . ",";
                                
                            // }
                            // $fullname .= $speciality_str;
                            $fullname = $user->fullname;
                            

                        }else{
                            $user_type = 'undefined';
                        }
                        //$links = 'http://localhost/danh-sach/bac-si/'.$link;

                        header('Content-Type: application/json; charset=utf-8');
                        return json_encode(array('isLogin' => true,'msg'=>'Login Success','user_type'=>$user_type,'paid'=>$user->paid,'fullname'=>$fullname,'id'=>$id),JSON_UNESCAPED_UNICODE);
                    }else{
                        return json_encode(array('isLogin' => false,'msg'=>'Incorrect password')); 
                    } 
                //}
                  
                //return json_encode(array('isLogin' => false,'msg'=>'Account has expired'));
            }else{
                return json_encode(array('isLogin' => false,'msg'=>'Account does not exist')); 
            }
        }
    }
    public function timesCall(Request $rq)
    {

        $user_email = $rq->get('user_email'); 
        $doctor_email = $rq->get('doctor_email');
        $times = (int)$rq->get('times') - 10;
       
        if(empty($user_email)) return json_encode(array('isSave' => false,'msg'=>'user_email is not required'));
        if(empty($doctor_email)) return json_encode(array('isSave' => false,'msg'=>'doctor_email is not required'));
        if(empty($times)) return json_encode(array('isSave' => false,'msg'=>'Times is not required'));

        

        $calltime = new Calltime;          
        $calltime->user_email = $user_email;
        $calltime->doctor_email= $doctor_email;
        $calltime->times = $times/60;
        
        if($calltime->save()){
            return json_encode(array('isSave' => true,'msg'=>'Save Success')); 
        }else{
            return json_encode(array('isSave' => false,'msg'=>'Save Fail')); 
        }

    }

    public function postDangnhap(Request $rq){
    	$email = $rq->input('email');
    	$pass= $rq->input('password');
        
    	$next = $rq->input('next');
    	if(!$rq->session()->has('user')){
	    	$user = Users::where('email',$email)->first();
	    	if($user!=null){
	    		if(Hash::check($pass,$user->password)){
	    			$rq->session()->put('user',$user);	    			
	    			return redirect('/');
	    		}else{
                    $errors = new MessageBag(['errorlogin' => 'Email hoặc mật khẩu không đúng']);
                    return redirect()->back()->withInput()->withErrors($errors);
                } 
	    	}else{
                $errors = new MessageBag(['errorlogin' => 'Email hoặc mật khẩu không đúng']);
                return redirect()->back()->withInput()->withErrors($errors);
            }	    	
    	}
    }

    public function taikhoan(Request $rq){
    	if($rq->session()->has('user')){
    		return view('taikhoan_home');
    	}
    	else{
    		return redirect('/dang-nhap?next=/tai-khoan');
    	}
    }
    public function taikhoan_method(Request $rq, $method){
    	if($rq->session()->has('user')){
	    	switch($method){
	    		case "ghi-nho":
	    			 return $this->taikhoan_ghinho($rq);
	    			break;
	    		case "nhan-xet":
	    			return $this->taikhoan_nhanxet($rq);
	    			break;
	    		case "hoi-dap":

	    			return $this->taikhoan_hoidap($rq);
	    			break;
	    		case "doi-mat-khau":
	    			return $this->taikhoan_doimatkhau($rq);
	    			break;
	    		case "cau-hoi-moi-nhat":
	    			$all = \App\Answer::pluck('question_id')->all();
	    			if($rq->session()->get('user')->user_type_id ==2){
	    				 $doctorid = $rq->session()->get('user')->doctor->doctor_id;
	    				 $spec = \App\DoctorSpeciality::where('doctor_id',$doctorid)->pluck('speciality_id')->all();
	    				// var_dump($spec);
	    				 $questions = \App\Question::whereNotIn('question_id',$all)->whereIn('speciality_id',$spec)->select('*')->get();
	    				//var_dump($questions);
	    				 view()->share('questions',$questions);
	    				 return view('taikhoan_cauhoimoinhat');
	    			    }
                   
	    			break;
	    		case "them-bac-si":
	    			$speciality = \App\Speciality::all();
	    			$services = \App\Service::all();
	    			return view('taikhoan_thembacsi',['specialities'=>$speciality,'services'=>$services]);
	    			break;
	    		case "them-csyt":
	    			$speciality = \App\Speciality::all();
	    			$services = \App\Service::all();
	    			return view('taikhoan_themcsyt',['specialities'=>$speciality,'services'=>$services]);
	    			break;
	    		case "viet-bai":
	    			$catalogs = Catalog::all();
	    			return view('taikhoan_vietbai',['catalogs'=>$catalogs]);
	    			break;
	    	}
    	}
    	else{
    		return redirect('/dang-nhap?next=/tai-khoan');
    	}
    }
    
    public static function taikhoan_ghinho(Request $rq){
    	return view('taikhoan_ghinho');
    }
    public static function taikhoan_nhanxet(Request $rq){
    	return 'somming soon';
    }
    public static function taikhoan_hoidap(Request $rq){
    	$all = \App\Answer::pluck('question_id')->all();
        $user_id = $rq->session()->get('user')->user_id;
      
         $count_answers = 0;
         //var_dump($user_id);
      //  $questions = Question::where('user_id',$user_id)->get();
      if( $rq->session()->get('user')->user_type_id==2 && $rq->session()->get('user')->doctor!=null){
      
        $spec = \App\DoctorSpeciality::where('doctor_id',$rq->session()->get('user')->doctor->doctor_id)->pluck('speciality_id')->all();
        //var_dump($spec);return;
        $questions = \App\Question::whereNotIn('question_id',$all)->whereIn('speciality_id',$spec)->select('*')->orderBy('created_at','DESC')->paginate(20);
        $answers = \App\Question::whereIn('question_id',$all)->whereIn('speciality_id',$spec)->select('*')->orderBy('created_at','DESC')->paginate(20);
      }
      elseif($rq->session()->get('user')->user_type_id==3 &&  $rq->session()->get('user')->clinic!=null){
      	$spec = \App\ClinicSpeciality::where('clinic_id',$rq->session()->get('user')->clinic->clinic_id)->pluck('speciality_id')->all();
      	//  var_dump($spec);
      	$questions = \App\Question::whereNotIn('question_id',$all)->whereIn('speciality_id',$spec)->select('*')->orderBy('created_at','DESC')->paginate(20);
      	$answers = \App\Question::whereIn('question_id',$all)->whereIn('speciality_id',$spec)->select('*')->orderBy('created_at','DESC')->paginate(20);
      	 
      }
      else{
      
      	$questions = \App\Question::where('user_id',$user_id)->orderBy('created_at','DESC')->paginate(20);
      	
      	$answers = \App\Question::where('user_id',$user_id)->whereIn('question_id',$all)->orderBy('created_at','DESC')->paginate(20);
      	//echo count($answers);return;
      }
      
        //$answers = \App\Question::whereIn('question_id',$all)->whereIn('speciality_id',$spec)->select('*')->get();
       // var_dump($spec);return;
        foreach ($questions as $question) {
             $dem  = Answer::where('question_id',$question['question_id'])->count();
             $count_answers=  $dem +1;
              
            } $count_answers =$count_answers + $count_answers;
       $count_questions =Question::where('user_id',$user_id)->count();
    	return view('taikhoan_hoidap',['questions' => $questions,'count_answers' => $count_answers,'count_questions' => $count_questions,'answers'=>$answers]);

    }
    public static function taikhoan_doimatkhau(Request $rq){
    	return view('taikhoan_doimatkhau');
    }
    public function doimatkhau(Request $rq){
    	$pass = $rq->input('password');
    	$newpass = $rq->input('new_password');
    	$newpass_confirm = $rq->input('new_password_confirm');
    	$email = $rq->session()->get('user')->email;
    	$user = Users::where('email',$email)->first();
    	if($user!=null){
    		if(Hash::check($pass,$user->password)){
    			if($newpass==$newpass_confirm){
    				$user->password= Hash::make($newpass);
    				$user->save();
    			}
    			else{
    				return response()->json(array('detail'=>'New password invalid'),400);
    			}
    		}
    		else{
    			return response()->json(array('detail'=>'Password invalid'),400);
    		}
    	}
    }
    
    public function dangxuat(Request $rq){
        Session::flush();
    	$rq->session()->forget('user'); 
        return redirect('/home');  
        
    }
    public function khuyenmai(Request $rq){
    	//echo 'khuyen mai';
    	$deal_category = \App\DealCategory::all();
    	$category = $rq->input('categories');
    	$cate = \App\DealCategory::where('category_url',$category)->first();
    	if($cate!=null){
    		$deals = \App\Deals::where('deal_category',$cate->id)->paginate(30);
    	}
    	else{

    		$deals = \App\Deals::orderBy('ranked','DESC')->paginate(30);
    	}

    	return view('khuyenmai',['deal_category'=>$deal_category,'deals'=>$deals])->withPost($deals);
    }
    public function benh(Request $rq){
    	  $benh_view = Disease::groupBy('view')->orderBy('view','DESC')->get();
          return view('benh',['benh_view'=> $benh_view]);
    }
    public function chitietbenh($qid)
    {
        $ads = Ads::where('place','3')->get();
    	$url = explode('-',$qid);
    	$id = $url[count($url)-1];
    	//$bacsi =
        
    	$benh = Disease::find($id);
    	$cauhoi = Question::where('speciality_id',$benh['speciality_id'])->get();
        $id_bacsi = DoctorSpeciality::where('speciality_id',$benh['speciality_id'])->pluck('doctor_id')->all();
		//var_dump($id_bacsi);return;
		$idClinic = ClinicSpeciality::where('speciality_id',$benh['speciality_id'])->pluck('clinic_id')->all();
		$doctor = Doctor::whereIn('doctor_id',$id_bacsi)->take(10)->get();
		$clinics = Clinic::whereIn('clinic_id',$idClinic)->take(10)->get();
    	return view('benh-detail',['benh'=>$benh,'cauhoi'=>$cauhoi,'doctors'=>$doctor,'clinics'=>$clinics, 'ads'=>$ads]);
    }
    
    
    public function thuoc(Request $rq){
    	$medicines = Medicine::orderBy('medicine_id','DESC')->paginate(60);
    	if($rq->input('q')){
    		$q = urldecode($rq->input('q'));
    		$benh = Disease::where('disease_name','like','%'.$q.'%');
    		$benh_count = $benh->count();
    		$benh = $benh->paginate(30);
    		$thuoc = Medicine::where('description','like','%'.$q.'%')->count();
    		$medicines = Medicine::where('description','like','%'.$q.'%')->paginate(60);
    		$bs = Doctor::where('doctor_name','like','%'.$q.'%')->count();
    		$csyt = Clinic::where('clinic_name','like','%'.$q.'%')->count();
    		$qs = Question::where('question_title','like','%'.$q.'%')->count();
    		$service = \App\Service::where('service_name','like','%'.$q.'%')->count();
    		return view('thuoc',['medicines'=>$medicines,'count'=>$benh_count,'benh'=>$benh,'thuoc'=>$thuoc,'doctor'=>$bs,'clinic'=>$csyt,'question'=>$qs,'service'=>$service])->withPost($medicines);
    	}
    	
    	return view('thuoc',['medicines'=>$medicines])->withPost($medicines);
    }
    public function chitietthuoc($qid){
        $ads = Ads::where('place','4')->get();

    	$url = explode('-',$qid);
    	$id = $url[count($url)-1];
    	//echo $id;return;
    	$thuoc = Medicine::find($id);
    	//var_dump($thuoc);return;
    	//var_dump($thuoc->type_medicine);
        if($thuoc->speciality_id!=null){
    		$lienquan =Medicine::where('speciality_id',$thuoc->speciality_id)->get();
    		view()->share('lienquan',$lienquan);
        }
    	//$lienquan = Medicine::all()->get(5);
    	//var_dump($lienquan);return;
    	return view('thuoc-detail',['thuoc'=>$thuoc,'ads'=>$ads]);
    }
    function to_slug($str) {
    	$str = trim(mb_strtolower($str));
    	$str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
    	$str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
    	$str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
    	$str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
    	$str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
    	$str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
    	$str = preg_replace('/(đ)/', 'd', $str);
    	$str = preg_replace('/[^a-z0-9-\s]/', '', $str);
    	$str = preg_replace('/([\s]+)/', '-', $str);
    	return $str;
    }
    public function comment($qid,Request $rq){
    	$url = explode('-',$qid);
    	$id = $url[count($url)-1];
    	$baiviet =Article::find($id);
    	$comment = new comment;
    	$comment->article_id = $id;
    	$comment->user_id=$rq->session()->get('user')->user_id;
    	$comment->content= $rq->input('body');
    	$comment->save();
    	//$tieude = to_slug($baiviet['article_title']);
//return redirect('bai-viet/'.$tieude.'-'.$id)->with('thongbao','Viết Bình Luận Thành Công');
        return redirect()->back()->with('thongbao','Viết Bình Luận Thành Công');
    }

    public function commentdoctor($qid,Request $rq){
        $comment = new comment;
        $comment->doctor_id = $qid;
        $comment->user_id=$rq->session()->get('user')->user_id;
        $comment->content= $rq->input('comment');
        $comment->save();
        return redirect()->back()->with('thongbao','Viết Bình Luận Thành Công');
    }
    public function commentclinic($qid,Request $rq){

		$comment = new comment;
		$comment->clinic_id = $qid;
		$comment->user_id=$rq->session()->get('user')->user_id;
		$comment->feedback_=$rq->input('rating');
		$comment->content= $rq->input('body');
		// $comment->name= $rq->input('name');
		//$comment->email= $rq->input('email');
		$comment->save();
		//$tieude = to_slug($baiviet['article_title']);
		return redirect()->back()->with('thongbao','Bạn Bình Luận Thành Công');
	
    }
    public function detail($id){

           $comment  = comment::where('article_id',$id)->orderBy('created_at','DESC')->get();


          $Catalog = Catalog::all();
          $baiviet_new=Article::orderBy('article_id','DESC')->limit(1)->first();
          $baiviets    =Article::orderBy('article_id','DESC')->limit(5)->get();
          $baiviet = Article::find($id);
            $lienquan =Article::where('catalog_id',$baiviet['catalog_id'])->orderBy('article_id','DESC')->limit(5)->get();
          $noibat   =Article::orderBy('created_at','DESC')->limit(5)->get();// ,'noibat' =>$noibat

          return view('detail',['baiviet' => $baiviet,'lienquan' => $lienquan,'noibat' => $noibat,'comment' => $comment]);
    }

 
    public function chitietbaiviet($qid){
        $ads = Ads::where('place','2')->get();


         $url = explode('-',$qid);   
         $id = $url[count($url)-1];
         $Catalogs = Catalog::all();
          $comment  = comment::where('article_id',$id)->orderBy('created_at','DESC')->get();
          $Catalog = Catalog::all();
          $baiviet_new=Article::orderBy('article_id','DESC')->limit(1)->first();
          $baiviets    =Article::orderBy('article_id','DESC')->limit(5)->get();
          $baiviet = Article::find($id);
          $lienquan =Article::where('catalog_id',$baiviet['catalog_id'])->orderBy('article_id','DESC')->limit(5)->get();
          $noibat   =Article::orderBy('created_at','DESC')->limit(5)->get();// ,'noibat' =>$noibat
          return view('detail',['Catalogs'=>$Catalogs,'baiviet' => $baiviet,'lienquan' => $lienquan,'noibat' => $noibat,'comment' => $comment, 'ads' => $ads] );

    }
    public function chuyenmuc($qid) {
        $url = explode('-',$qid);   
        $id = $url[count($url)-1];
        $Catalogs = Catalog::all();
        $Catalog = Catalog::where('id',$id)->first();
        if($Catalog->parent_id == 0)
        {
          $baiviet_new=Article::where('catalog_id',$id)->orderBy('article_id','ASC')->limit(1)->first();
          $baiviet = Article::where('catalog_id',$id)->orderBy('article_id','ASC')->paginate(10);
          return view('danhmuc',['Catalogs' => $Catalogs,'baiviet' => $baiviet,'baiviet_new'=>$baiviet_new]);
         }
        else

        $baiviet_new=Article::where('catalog_id',$id)->orderBy('article_id','DESC')->limit(1)->first();
        $baiviet = Article::where('catalog_id',$id)->orderBy('article_id','ASC')->paginate(10);
        return view('danhmuc',['Catalogs' => $Catalogs,'baiviet' => $baiviet,'baiviet_new'=>$baiviet_new])->withPost($baiviet);
        
    }
       public function get() {
        $Catalog = Catalog::all();
        $baiviet_new=Article::orderBy('article_id','DESC')->limit(1)->first();
        $baiviets  =Article::orderBy('article_id','DESC')->limit(50)->get();
        return view('baiviet-list',['baiviets' => $baiviets,'Catalog' => $Catalog,'baiviet_new'=>$baiviet_new])->withPost($baiviets);

    }
    public function danhmuc($id)
    {   
           $Catalog = Catalog::find($id);
       
           $baiviet_new = Article::where('catalog_id',$id)->orderBy('article_id','ASC')->first();
           if(!$baiviet_new)
           {
            $baiviet_new = null;
           }
           $baiviet =Article::where('catalog_id',$id)->orderBy('article_id','ASC')->limit(8)->get();
          

        return view('danhmuc',['baiviet' => $baiviet,'Catalog'=>$Catalog,'baiviet_new' => $baiviet_new])->withPost($baiviet);;
    }
    public function vietbai(Request $rq){
    	$title = $rq->input('tieude');
    	//echo $title;
    	$tomtat =$rq->input('tomtat');
    	$noidung = $rq->input('noidung');
    	$chuyenmuc=  $rq->input('chuyenmuc');
    	$author = $rq->input('nguoiviet');
    	$source = $rq->input('nguon');
    	$article = new Article;
    	$article->catalog_id = $chuyenmuc;
    	$article->article_title = $title;
    	$article->article_content = $noidung;
    	$article->article_summary = $tomtat;
    	$article->writer=$author;
    	$article->article_url = "";
        //upload file
        if ($rq->hasFile('hinhanh')) {
            $file = $rq->file('hinhanh');
            $random_digit = rand(000000000, 999999999);
            $name = $random_digit.$file->getClientOriginalName('hinhanh');
            $duoi = strtolower($file->getClientOriginalExtension('hinhanh'));

            if ($duoi != 'png' && $duoi != 'jpg' && $duoi != 'jpeg' && $duoi != 'svg') {
                return back()->with(['flash_level' => 'danger', 'flash_message' => 'Định dạng ảnh chưa chính xác']);
            }
            $file->move('public/images/', $name);
            $article->image = $name;         
        }
    	$article->save();        
    	return redirect('/bai-viet');
    }


    public function thembacsi(Request $rq){
        
    	$name = $rq->input('doctorname');
    	$desc = $rq->input('description');
    	$specialities = $rq->input('chuyenkhoa');
    	//var_dump( $specialities);
    	$services = $rq->input('dichvu');

    	$doctorclinic = $rq->input('noicongtac');



    	$exprience = $rq->input('kinhnghiem');
    	$exprience = explode("#",$exprience);
    	$daotao = $rq->input('daotao');
    	$daotao = explode('#',$daotao);
        $address = $rq->input('diachi');
        $doctortimework = $rq->input('doctortimework');

    	$doctor = new Doctor;
    	$doctor->doctor_name = $name;
        $doctor->doctor_address = $address;
        $doctor->doctor_timework = $doctortimework;
        $doctor->doctor_description = $desc;
        $doctor->doctor_clinic = $doctorclinic;
    	$exp = "<ul>";
    	foreach($exprience as $ex){
    		$exp.="<li>".$ex."</li>";
    	}
    	$exp.="</ul>";
    	$doctor->experience = $exp;
    	$dt = "<ul>";
    	foreach($daotao as $d){
    		$dt.="<li>".$d."</li>";
    	}
    	$dt.="</ul>";
    	$doctor->training = $dt;
    	$doctor->doctor_url = $this->to_slug($name);    	

        //upload file
        if ($rq->hasFile('hinhanh')) {
            $file = $rq->file('hinhanh');
            $random_digit = rand(000000000, 999999999);
            $name = $random_digit.$file->getClientOriginalName('hinhanh');
            $duoi = strtolower($file->getClientOriginalExtension('hinhanh'));

            if ($duoi != 'png' && $duoi != 'jpg' && $duoi != 'jpeg' && $duoi != 'svg') {
                return back()->with(['flash_level' => 'danger', 'flash_message' => 'Định dạng ảnh chưa chính xác']);
            }
            $file->move('public/images/doctor', $name);
            $doctor->profile_image = $name;           
        }    	
    	$doctor->save();

    	if($doctor->doctor_id!="" || $doctor->doctor_id!=null){
    		foreach($specialities as $sp){
    			$docsp = new DoctorSpeciality;
    			$docsp->doctor_id = $doctor->doctor_id;
    			$docsp->speciality_id = $sp;
    			$docsp->save();
    		}
    		foreach($services as $ser){
    			$docser = new DoctorService;
    			$docser->doctor_id = $doctor->doctor_id;
    			$docser->service_id = $ser;
    			$docser->save();
    		}
    	return redirect('/danh-sach/bac-si/'.$doctor->doctor_url.'-'.$doctor->doctor_id);
    	}
    }

    public function themcsyt(Request $rq){
    	//var_dump($rq);
    	$name = $rq->input('clinicname');
    	$specialities = $rq->input('chuyenkhoa');
    	//var_dump( $specialities);
    	$services = $rq->input('dichvu');
    	$clinic = new Clinic;
    	$clinic->clinic_name = $name;
    	$clinic->clinic_url  = $this->to_slug(trim($name));
    	$clinic->clinic_address = $rq->input('diachi');
    	$clinic->clinic_phone = $rq->input('dienthoai');
    	$clinic->clinic_desc = $rq->input('description');
        $clinic->clinic_timeopen = $rq->input('clinictimeopen');
       

        //upload images
        if ($rq->hasFile('hinhanh')) {
            $file = $rq->file('hinhanh');
            $random_digit = rand(000000000, 999999999);
            $name = $random_digit.$file->getClientOriginalName('hinhanh');
            $duoi = strtolower($file->getClientOriginalExtension('hinhanh'));

            if ($duoi != 'png' && $duoi != 'jpg' && $duoi != 'jpeg' && $duoi != 'svg') {
                return back()->with(['flash_level' => 'danger', 'flash_message' => 'Định dạng ảnh chưa chính xác']);
            }
            $file->move('public/images/health_facilities', $name);
            $clinic->profile_image = $name;        
        }   
    	
    	$clinic->save();
    	if($clinic->clinic_id!="" || $clinic->clinic_id!=null){
    		if($specialities!=null)
	    		foreach($specialities as $sp){
	    			$docsp = new ClinicSpeciality;
	    			$docsp->clinic_id = $clinic->clinic_id;
	    			$docsp->speciality_id = $sp;
	    			$docsp->save();
	    		}
    	
    		if($services!=null){
	    		foreach($services as $ser){
	    			$docser = new ClinicService;
	    			$docser->clinic_id = $clinic->clinic_id;
	    			$docser->service_id = $ser;
	    			$docser->save();
	    		}
    		}
    	return redirect('/co-so-y-te/'.$clinic->clinic_url.'-'.$clinic->clinic_id);
    	}
    	//echo $name;
    }
    // function test(){
    // 	return view('welcome');
    // }

    public function chat(){
        return view('chat');
    }
    public function quanli(){
        return view('admin/quanli');
    }
    public function quanlithuoc(){
        $medicines = Medicine::orderBy("medicine_id","DESC")->paginate(60);
        return view('admin/thuoc/medicine',compact("medicines"));
    }
    public function AddMedicine(){
        return view('admin/thuoc/add_medicine');
    }
    public function PostAddMedicine(Request $req){
        $medicines = new Medicine;
        $medicines->description = $req->tenthuoc;
        $medicines->packing = $req->donggoi;
        $medicines->guide = $req->huongdan;
        $medicines->duration = $req->thoihan;
        $medicines->registered_number = $req->sodangki;
        $medicines->warning_medicine = $req->luuy;
        $medicines->assign_medicine = $req->chidinh;
        $medicines->contraindication_medicine = $req->chongchidinh;
        $medicines->side_effect_medicine = $req->ngoaile;
        $medicines->careful_medicine = $req->thantrong;
        $medicines->overdose_medicine = $req->socthuoc;
        $medicines->preservation_medicine = $req->baoquan;
        $medicines->forget_take_medicine = $req->chonggia;
        $medicines->interactive_medicine = $req->tuongtac;
        $medicines->pharmacokinetic_medicine = $req->duocdonghoc;
        $medicines->type_medicine = $req->loaithuoc;
        $medicines->dosage_forms = $req->dangthuoc;
        $medicines->info_drugs = $req->thongtin;
        $medicines->image = $_FILES['hinhanh']['name'];
        $medicines->contact = $req->lienhe;
        move_uploaded_file($_FILES['hinhanh']['tmp_name'],"upload/thuoc/".$_FILES['hinhanh']['name']."");
        $medicines->save();
        return redirect("/admin/thuoc");
    }
    public function DeleteMedicine($id){
        $medicines = Medicine::find($id);
        $medicines->delete();
        return redirect("/admin/thuoc");
    }

    public function EditMedicine($id){
        $medicines = Medicine::find($id);
        return view('/admin/thuoc/editMedicine', compact('medicines'));
    }
    public function PostEditMedicine($id, Request $req){
        $medicines = Medicine::find($id);
        $medicines->description = $req->tenthuoc;
        $medicines->packing = $req->donggoi;
        $medicines->guide = $req->huongdan;
        $medicines->duration = $req->thoihan;
        $medicines->registered_number = $req->sodangki;
        $medicines->warning_medicine = $req->luuy;
        $medicines->assign_medicine = $req->chidinh;
        $medicines->contraindication_medicine = $req->chongchidinh;
        $medicines->side_effect_medicine = $req->ngoaile;
        $medicines->careful_medicine = $req->thantrong;
        $medicines->overdose_medicine = $req->socthuoc;
        $medicines->preservation_medicine = $req->baoquan;
        $medicines->forget_take_medicine = $req->chonggia;
        $medicines->interactive_medicine = $req->tuongtac;
        $medicines->pharmacokinetic_medicine = $req->duocdonghoc;
        $medicines->type_medicine = $req->loaithuoc;
        $medicines->dosage_forms = $req->dangthuoc;
        $medicines->info_drugs = $req->thongtin;
        $medicines->image = $_FILES['hinhanh']['name'];
        $medicines->contact = $req->lienhe;
        move_uploaded_file($_FILES['hinhanh']['tmp_name'],"upload/thuoc/".$_FILES['hinhanh']['name']."");
        $medicines->save();
        return redirect("/admin/thuoc");
    }

    public function quanlibenh(){
        $diseases = Disease::orderBy("disease_id","DESC")->paginate(60);
        return view('admin/benh/disease',compact('diseases'));
    }
    public function addDisease(){
        return view('admin/benh/addDisease');
    }
    public function PostaddDisease(Request $req){
        $disease = new Disease;
        $disease->disease_name = $req->tenbenh;
        $disease->disease_content = $req->noidungbenh;
        $disease->disease_image = $_FILES['hinhanh']['name'];
        $disease->overview = $req->tongquan;
        $disease->cause = $req->nguyennhan;
        $disease->prevent = $req->nganchan;
        $disease->treatment = $req->cachdieutri;
        $disease->view = $req->luotxem;

        move_uploaded_file($_FILES['hinhanh']['tmp_name'],"upload/benh/".$_FILES['hinhanh']['name']."");

        $disease->save();
        return redirect('/admin/benh');
    }
    public function DeleteDisease($id){
        $disease = Disease::find($id);
        $disease->delete();
        return redirect("/admin/benh");
    }
    public function EditDisease($id, Request $req){
        $disease = Disease::find($id);
        return view ('/admin/benh/editDisease',compact('disease'));
    }

}


{{date('d/m/Y, H:m:i', strtotime($qs->created_at))}}


<?php
/**
 * Product   CometChat
 * Copyright (c) 2016 Inscripts
 * License: https://www.cometchat.com/legal/license

 * This is a installation file of Web-SDK. At the time of Web-SDK
 * installation you have to modify this file. This installation work for Different framework
 * Reference ULR : https://docs.cometchat.com/web-sdk/quick-start/
 *
 *
 * @category   Installation
 * @package    CometChat
 * @class      Integration
 * @author     Cometchat
 * @since      NA
 * @deprecated NA
 */
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/* ADVANCED */

/**
* CMS Name
* @var string
*/
$cms = "standalone";

/**
* Database type
* @var string
*/
$dbms = "mysql";

/**
* If client want to integrate role base access control in his system. He have change value to "1"
* $role_base_access = 0; (Role base access control is deactive)
* $role_base_access = 1; (Role base access control is active)
* @var string
*/
$role_base_access = 0;

/**
* If client want to integrate Credit System for CometChat features. He have change value to "1"
* $enabled_credit = 0; (Credit system for CometChat feature deactive)
* $enabled_credit = 1; (Credit system for CometChat feature active)
* @var string
*/
$enabled_credit = 0;

define('SET_SESSION_NAME','');
define('SWITCH_ENABLED','1');
define('INCLUDE_JQUERY','1');
define('FORCE_MAGIC_QUOTES','0');
define('UNIQUE_CACHE_KEY','0'); // SET to 1 when getFriendList function is Configured manually

if($dbms == "mssql" && file_exists(dirname(__FILE__).DIRECTORY_SEPARATOR.'sqlsrv_func.php')){
    include_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'sqlsrv_func.php');
}

/**
* DATABASE SETTINGS
* DB_SERVER : database server name. @var string
* DB_PORT : database port id. @var integer
* DB_USERNAME : database user name. @var string
* DB_PASSWORD : database Password. @var string
* DB_NAME : database name.  @var string
*/
define('DB_SERVER',         "localhost"             );
define('DB_PORT',           "3306"                  );
define('DB_USERNAME',       "bacsiviet"                 );
define('DB_PASSWORD',       "B9mvy85f965bu*5X74ks2695w5Gd4R"                );
define('DB_NAME',           "bacsiviet_db"          );

/**
* $table_prefix : table prefix
* If table prefix is not present keep it blank.
* @var string
*/
$table_prefix = '';

/**
* $db_usertable : User table name
* Users or members information table name.
* @var string
*/
$db_usertable = 'user';

/**
* $db_usertable_userid : UserId field in user / Member table. Its a primary key of user table.
* @var string
*/
$db_usertable_userid = 'user_id';

/**
* $db_usertable_name : User Name containing field in the users or members table.
* @var string
*/
$db_usertable_name = 'fullname';

/**
* $db_avatartable : avatar field name.
* @var string
* If avatar is in same table enter 'avatar' table name
* If avatar is in another table use left join for it.
* Example : LEFT JOIN avatar_table ON user.id = avator_table.userid
*/
$db_avatartable = ' ';

/**
* $db_avatarfield : avatar field name.
* @var string
*/
$db_avatarfield = ' '.$table_prefix.$db_usertable.'.'.$db_usertable_userid.' ';

/**
* $db_linkfield : profile link field.
* @var string
*/
$db_linkfield = ' '.$table_prefix.$db_usertable.'.'.$db_usertable_userid.' ';


class Integration{

  /**
  * Constructor defining and default variable defining
  */
    function __construct(){
        if(!defined('TABLE_PREFIX')){
            $this->defineFromGlobal('table_prefix');
            $this->defineFromGlobal('db_usertable');
            $this->defineFromGlobal('db_usertable_userid');
            $this->defineFromGlobal('db_usertable_name');
            $this->defineFromGlobal('db_avatartable');
            $this->defineFromGlobal('db_avatarfield');
            $this->defineFromGlobal('db_linkfield');
            $this->defineFromGlobal('role_base_access');
            $this->defineFromGlobal('enabled_credit');
        }
    }

  /**
  * Define Global variables & unset it.
  */
    function defineFromGlobal($key){
        if(isset($GLOBALS[$key])){
            define(strtoupper($key), $GLOBALS[$key]);
            unset($GLOBALS[$key]);
        }
    }

  /**
  * get user id
  * @param -
  * @return (integer) ($userid)
  */
    function getUserID() {
        $userid = 0;
        if (!empty($_SESSION['basedata']) && $_SESSION['basedata'] != 'null') {
            $_REQUEST['basedata'] = $_SESSION['basedata'];
        }

        if (!empty($_REQUEST['basedata'])) {

            if (function_exists('mcrypt_encrypt') && defined('ENCRYPT_USERID') && ENCRYPT_USERID == '1') {
                $key = "";
                if( defined('KEY_A') && defined('KEY_B') && defined('KEY_C') ){
                    $key = KEY_A.KEY_B.KEY_C;
                }
                $uid = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode(rawurldecode($_REQUEST['basedata'])), MCRYPT_MODE_CBC, md5(md5($key))), "\0");
                if (intval($uid) > 0) {
                    $userid = $uid;
                }
            } else {
                $userid = $_REQUEST['basedata'];
            }
        }
        if (!empty($_SESSION['userid_chat'])) {
            $userid = $_SESSION['userid_chat'];
        }

        $userid = intval($userid);
        return $userid;
    }

  /**
  * Chat login
  * @param - (integer) ($userName), (string) ($userPass)
  * @return (integer) ($userid)
  */
    function chatLogin($userName,$userPass) {

        $userid = 0;
        global $guestsMode;

    /** TODO: Modifiable SQL query START **/
        /* The SQL query should return user details */
        if (filter_var($userName, FILTER_VALIDATE_EMAIL)) {
            $sql = ("SELECT * FROM `".TABLE_PREFIX.DB_USERTABLE."` WHERE email = '".sql_real_escape_string($userName)."'");
        } else {
            $sql = ("SELECT * FROM `".TABLE_PREFIX.DB_USERTABLE."` WHERE ".DB_USERTABLE_NAME." = '".sql_real_escape_string($userName)."'");
        }
    /** Modifiable SQL query END **/

        $result = sql_query($sql, array(), 1);
        $row = sql_fetch_assoc($result);
        /* Add your password validation mechanism here */
        //$salted_password = md5($row['value'].$userPass.$row['salt']);

        if($row['password'] == $userPass) { //replace $userPass with $salted_password if you have edited the password validation mechanism
            $userid = $row[DB_USERTABLE_USERID];
        }
        if(!empty($userName) && !empty($_REQUEST['social_details'])) {
            $social_details = json_decode($_REQUEST['social_details']);
            $userid = socialLogin($social_details);
        }
        if(!empty($_REQUEST['guest_login']) && $userPass == "CC^CONTROL_GUEST" && $guestsMode == 1){
            $userid = getGuestID($userName);
        }
        if(!empty($userid) && isset($_REQUEST['callbackfn']) && $_REQUEST['callbackfn'] == 'mobileapp'){
            $sql = ("insert into cometchat_status (userid,isdevice) values ('".sql_real_escape_string($userid)."','1') on duplicate key update isdevice = '1'");
                sql_query($sql, array(), 1);
        }
        if($userid && function_exists('mcrypt_encrypt') && defined('ENCRYPT_USERID') && ENCRYPT_USERID == '1') {
            $key = "";
            if( defined('KEY_A') && defined('KEY_B') && defined('KEY_C') ){
                $key = KEY_A.KEY_B.KEY_C;
            }
            $userid = rawurlencode(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $userid, MCRYPT_MODE_CBC, md5(md5($key)))));
        }

        return $userid;
    }

  /**
  * Get Online Friends List
  * @param - (integer) ($userid), (timestamp) ($time)
  * @return (string) ($sql)
  */
    function getFriendsList($userid,$time) {
        global $hideOffline;
        $offlinecondition = '';
        $sql = ("select DISTINCT ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_USERID." userid, ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_NAME." username, ".DB_LINKFIELD." link, ".DB_AVATARFIELD." avatar, cometchat_status.lastactivity lastactivity, cometchat_status.lastseen lastseen, cometchat_status.lastseensetting lastseensetting, cometchat_status.status, cometchat_status.message, cometchat_status.isdevice, cometchat_status.readreceiptsetting readreceiptsetting from ".TABLE_PREFIX."friends join ".TABLE_PREFIX.DB_USERTABLE." on  ".TABLE_PREFIX."friends.toid = ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_USERID." left join cometchat_status on ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_USERID." = cometchat_status.userid ".DB_AVATARTABLE." where ".TABLE_PREFIX."friends.fromid = '".sql_real_escape_string($userid)."' order by username asc");
        if ((defined('MEMCACHE') && MEMCACHE <> 0) || DISPLAY_ALL_USERS == 1) {
            if ($hideOffline) {
                $offlinecondition = "where ((cometchat_status.lastactivity > (".sql_real_escape_string($time)."-".((ONLINE_TIMEOUT)*2).")) OR cometchat_status.isdevice = 1) and (cometchat_status.status IS NULL OR cometchat_status.status <> 'invisible' OR cometchat_status.status <> 'offline')";
            }
            $sql = ("select ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_USERID." userid, ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_NAME." username, ".DB_LINKFIELD." link, ".DB_AVATARFIELD." avatar, cometchat_status.lastactivity lastactivity, cometchat_status.lastseen lastseen, cometchat_status.lastseensetting lastseensetting, cometchat_status.status, cometchat_status.message, cometchat_status.isdevice, cometchat_status.readreceiptsetting readreceiptsetting from  ".TABLE_PREFIX.DB_USERTABLE."   left join cometchat_status on ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_USERID." = cometchat_status.userid ".DB_AVATARTABLE." ".$offlinecondition ." order by username asc");
        }

        return $sql;
    }

   /**
  * Get Friends Ids
  * @param - (integer) $userid
  * @return (string) ($sql)
  */
    function getFriendsIds($userid) {

        $sql = ("SELECT toid as friendid FROM `friends` WHERE status =1 and fromid = '".sql_real_escape_string($userid)."' union SELECT fromid as myfrndids FROM `friends` WHERE status = 1 and toid = '".sql_real_escape_string($userid)."'");

        return $sql;
    }

  /**
  * Get User Details
  * @param - (integer) $userid
  * @return (string) ($sql)
  */
    function getUserDetails($userid) {
        $sql = ("select ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_USERID." userid, user.phone phone, ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_NAME." username, ".DB_LINKFIELD." link, ".DB_AVATARFIELD." avatar, cometchat_status.lastactivity lastactivity, cometchat_status.lastseen lastseen, cometchat_status.lastseensetting lastseensetting, cometchat_status.status, cometchat_status.message, cometchat_status.isdevice, cometchat_status.readreceiptsetting readreceiptsetting from ".TABLE_PREFIX.DB_USERTABLE." left join cometchat_status on ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_USERID." = cometchat_status.userid ".DB_AVATARTABLE." where ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_USERID." = '".sql_real_escape_string($userid)."'");

        // var_dump($sql);die;

        return $sql;
    }

   /**
  * Get Active Chatbox Details
  * @param - (integer) $userid
  * @return (string) ($sql)
  */
    function getActivechatboxdetails($userids) {
        $sql = ("select DISTINCT ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_USERID." userid, ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_NAME." username, ".DB_LINKFIELD." link, ".DB_AVATARFIELD." avatar, cometchat_status.lastactivity lastactivity, cometchat_status.lastseen lastseen, cometchat_status.lastseensetting lastseensetting, cometchat_status.status, cometchat_status.message, cometchat_status.isdevice, cometchat_status.readreceiptsetting readreceiptsetting from ".TABLE_PREFIX.DB_USERTABLE." left join cometchat_status on ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_USERID." = cometchat_status.userid ".DB_AVATARTABLE." where ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_USERID." IN (".$userids.")");

        return $sql;
    }

  /**
  * Get User Status
  * @param - (integer) $userid
  * @return (string) ($sql)
  */
    function getUserStatus($userid) {
        $sql = ("select cometchat_status.message, cometchat_status.lastseen lastseen, cometchat_status.lastseensetting lastseensetting, cometchat_status.status from cometchat_status where userid = '".sql_real_escape_string($userid)."'");

        return $sql;
    }

  /**
  * Fetch Link
  * user profile URL
  * @param - (string) $link
  * @return ''
  */
    function fetchLink($link) {
        return '';
    }

  /**
  * Get User Avatar URL
  * user Avatar URL
  * @param - (string) $image
  * @return default avatar
  */
    function getAvatar($image) {
        return BASE_URL.'images/noavatar.png';
    }

    function getTimeStamp() {
        return time();
    }

    function processTime($time) {
        return $time;
    }

  /**
  * Get Users Role
  * get users current role
  * @param - (string) $image
  * @return role
  */
    function getRole($userid){
        $role = 'default';
        global $userid;

        $sql = ("SELECT `role` FROM `users` WHERE `id` = '".$userid."'");
        $result = sql_query($sql, array(), 1);
        if($user = sql_fetch_assoc($result)){
            if(!empty($user['role']) && $user['role']<>'NULL'){
                $role = $user['role'];
            }
        }
        return $role;
    }

  /**
  * Get All Roles Details
  *
  * @param - (string) $role
  * @return (array)  $roles
  */
    function getRolesDetails($role = ''){
        global $guestsMode, $firstguestID;
        $roles = array();
        $sql = ("SELECT DISTINCT `role` FROM `users` order by `role`");
        if(!empty($role)){
            $sql = ("SELECT DISTINCT `role` FROM `users` WHERE `role`='".sql_real_escape_string($role)."'");
        }
        $result = sql_query($sql, array(), 1);
        while ($role = sql_fetch_assoc($result)) {
            if($role['role']=='NULL'||$role['role']==''){
                $role['role'] = 'default';
            }
            $roles[$role['role']] = array('name' => ucwords($role['role']));
        }
        if(empty($roles['default'])){
            $roles['default'] = array('name' => 'Default');
        }
        if ($guestsMode) {
            $roles["guest"] = array("name" => "Guest");
        }
        return $roles;
    }

/**
* Get Credits Details
*
* This function should return the number of credits present for a user
* @param -
* @return (integer)  $credits
*/
function getCredits(){
        /**
            The function returns the current value of the credits for a loggedin user.
            The developers can modify the SQL query to retrieve the credits available with the user.
        **/
        global $userid;
        $credits = 0;

        /** TODO: Modifiable SQL query START **/
        /* The SQL query should return the credits for $userid */
        $sql = ("SELECT `credits` FROM `users` WHERE `id` = '".sql_real_escape_string($userid)."'");
        /** Modifiable SQL query END **/

        $result = sql_query($sql, array(), 1);
        if($user = sql_fetch_assoc($result)){
            $credits = $user['credits'];
        }
        return $credits;
    }

  /**
  * Get Credits to Deduct
  *
  * returns the credit to deduct and the deduction interval
  * @param - (array) $params
  * @return (array)  $creditsinfo
  */
    function getCreditsToDeduct($params=array()){
        /**
            The function returns the returns the credit to deduct and the deduction interval.
            It is not recommended for the developers to modify the function.
        **/
        global $userid;
        $creditsinfo = array(
            'creditsToDeduct'=>0,
            'deductionInterval'=>0
        );

        $defaultParams = array(
            'type'=>'',
            'name'=>''
        );
        $params =  array_merge($defaultParams,$params);
        $role = !empty($params['role'])?$params['role']:$this->getRole($userid);
        $type = $params['type'];
        $name = $params['name'];
        if(!empty($role) && !empty($type) && !empty($name)){
            $rolefeature = $GLOBALS[$role.'_'.$type.($type!='core'?'s':'')];
            if(!empty($rolefeature) && !empty($rolefeature[$name]) && !empty($rolefeature[$name]['credit'])){
                $creditsinfo['creditsToDeduct'] =  $rolefeature[$name]['credit']['creditsToDeduct'];
                $creditsinfo['deductionInterval'] = $rolefeature[$name]['credit']['deductionInterval'];
            }
        }
        return $creditsinfo;
    }

  /**
  * Deduct Credits
  *
  * The function deducts the credits from database.
  * @param - (array) $params
  * @return (array)  $response
  */
    function deductCredits($params){

        /**
            The function deducts the credits in database.
            The developers can modify the query to update the deducted credits to database.
        **/

        if(!defined('ENABLED_CREDIT') && ENABLED_CREDIT==0){
            return array('errorcode'=>1,'message'=>'Credit Deduction is disabled by the Administrator.');
        }
        global $userid;
        $defaultParams = array(
            'to'=>0,
            'isGroup'=>0
        );
        $params =  array_merge($defaultParams,$params);
        $response = array('success'=> false);
        $to = $params['to'];
        $isGroup = $params['isGroup'];
        $type = $params['type'];
        $name = $params['name'];

        if(!empty($params['creditsToDeduct']) && !empty($params['deductionInterval'])){
            $creditsToDeduct = abs($params['creditsToDeduct']);
            $deductionInterval = $params['deductionInterval'];
        }else{
            $creditsinfo =  $this->getCreditsToDeduct($params);
            $creditsToDeduct = abs($creditsinfo['creditsToDeduct']);
            $deductionInterval = $creditsinfo['deductionInterval'];
        }

        /*** Set credit deduction timer ***/
        if(empty($_SESSION['cometchat'])){
            $_SESSION['cometchat'] = array();
        }
        if(empty($_SESSION['cometchat']['creditsdeductiontimer'])){
            $_SESSION['cometchat']['creditsdeductiontimer'] = array();
        }
        if(empty($_SESSION['cometchat']['creditsdeductiontimer'][$type.$name.$to.$isGroup])){
            $_SESSION['cometchat']['creditsdeductiontimer'][$type.$name.$to.$isGroup] = 0;
        }


        $availableCredits = $this->getCredits();

        if($creditsToDeduct==0){
            $response['errorcode'] = '2';
            $response['message'] = 'The Credit Deduction is not enabled for the '.$name.' '.$type.' for the '.$role.' role';
            $response['balance'] = $availableCredits;
        }elseif($creditsToDeduct > $availableCredits){
            $response['errorcode'] = '3';
            $response['message'] = $GLOBALS['language']['insufficient_credits'];
            $response['balance'] = $availableCredits;
        }elseif($_SESSION['cometchat']['creditsdeductiontimer'][$type.$name.$to.$isGroup]>time()-$deductionInterval*60){
            $response['errorcode'] = '4';
            $response['message'] = 'Already deducted '.$creditsToDeduct.' credits for the '.$type.' '.$name.' for the interval of '.$deductionInterval.' minutes';
            $response['balance'] = $availableCredits;
        }else{
            /** TODO: Modifiable SQL query START **/
            /* The SQL query should update the deducted credits for $userid */
            $sql = ("UPDATE `users` SET `credits` = `credits` - ".sql_real_escape_string($creditsToDeduct)." WHERE `id`='".sql_real_escape_string($userid)."'");
            /** Modifiable SQL query END **/
            $result = sql_query($sql, array(), 1);
            if(sql_affected_rows()>0){
                $_SESSION['cometchat']['creditsdeductiontimer'][$type.$name.$to.$isGroup] = time();
                $response['success'] = true;
                $response['message'] = 'Deducted '.$creditsToDeduct.' credits for using the '.$name.' '.$type.' for the interval of '.$deductionInterval.' minutes';
                $response['balance'] = $this->getCredits();
            }else{
                $response['errorcode'] = '5';
                $response['message'] = 'An error occurred while deducting credits from the database';
            }
        }
        return $response;
    }

 /* HOOKS */

  /**
  * hooks message
  *
  * This function inserts messages into Sites Messaging Table.
  * @param - (integer) $userid, (integer) $to, (string) $unsanitizedmessage, (integer) $dir, (string) $origmessage
  * @return -
  */
    function hooks_message($userid,$to,$unsanitizedmessage,$dir,$origmessage='') {
        global $language;
    }

  /**
  * hooks message
  *
  * This function forcefuly Add user into Contact List.
  * @param -
  * @return - (array)
  */
    function hooks_forcefriends() {

    }

  /**
  * hooks update Last Activity
  *
  * This function forcefuly Add user into Contact List.
  * @param - (integer) $userid
  * @return - (array)
  */
    function hooks_updateLastActivity($userid) {

    }

  /**
  * hooks update Last Activity
  *
  * This function forcefuly Add user into Contact List.
  * @param - (integer) $userid
  * @return - (array)
  */
    function hooks_statusupdate($userid,$statusmessage) {

    }

    function hooks_activityupdate($userid,$status) {

    }

}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/* LICENSE */

include_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'license.php');
$x = "\x62a\x73\x656\x34\x5fd\x65c\157\144\x65";
eval($x('JHI9ZXhwbG9kZSgnLScsJGxpY2Vuc2VrZXkpOyRwXz0wO2lmKCFlbXB0eSgkclsyXSkpJHBfPWludHZhbChwcmVnX3JlcGxhY2UoIi9bXjAtOV0vIiwnJywkclsyXSkpOw'));

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
