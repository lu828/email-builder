<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Request as Req;
use Illuminate\Support\Facades\DB;
use PDF;
class PDFController extends Controller{
    public function __construct()
    {
        $this->middleware('web');
    }
    public function index()
    {
        return view('pdf');
    }
    public function save(Request $request){
        $input = $request->all();
        $title = $input['html-file-name'];
        $folder_name = strtolower(str_replace(' ', '-', $title));
        $html = '';
        $html_editor = '';
        foreach( $input['pages'] as $page=>$content ) {
            $html .= $content;
        }
        foreach( $input['editor'] as $editor=>$editor_content ) {
            $html_editor .= $editor_content;
        }
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML($html);
        $tags = $doc->getElementsByTagName('img');
        foreach ($tags as $tag) {
            $old_src = $tag->getAttribute('src');
            $imgdata = base64_decode($old_src);
            $extension = '';
            $data = '';
            $image_type = substr($old_src, 5, strpos($old_src, ';')-5);
            if($image_type == 'image/png'){
                $data = str_replace('data:image/png;base64,', '', $old_src);
                $data = str_replace(' ', '+', $data);
                $data = base64_decode($data);
                $extension = '.png';
            }elseif($image_type == 'image/jpeg'){
                $data = str_replace('data:image/jpeg;base64,', '', $old_src);
                $data = str_replace(' ', '+', $data);
                $data = base64_decode($data);
                $extension = '.jpeg';
            }elseif($image_type == 'image/jpg'){
                $data = str_replace('data:image/jpg;base64,', '', $old_src);
                $data = str_replace(' ', '+', $data);
                $data = base64_decode($data);
                $extension = '.jpg';
            }elseif($image_type == 'image/gif'){
                $data = str_replace('data:image/gif;base64,', '', $old_src);
                $data = str_replace(' ', '+', $data);
                $data = base64_decode($data);
                $extension = '.gif';
            }
            if($extension != ''){
                if(!file_exists(public_path() ."/templates/".$folder_name."/images/")){
                    mkdir(public_path() ."/templates/".$folder_name."/images/",0777,true);
                }
                $file_name = uniqid();
                $file =  public_path() ."/templates/".$folder_name."/images/".$file_name . $extension;
                $success = file_put_contents($file, $data);
                if($success > 0){
                    $src =  url()->to('/')."/templates/".$folder_name."/images/".$file_name.$extension;
                }
                $tag->setAttribute('src', $src);
            }else{
                $old_src = $tag->getAttribute('src');
                $parsed = parse_url($old_src);
                if (empty($parsed['scheme'])) {
                    $tag->setAttribute('src', url()->to('/')."/".$old_src);
                }
            }
        }
        $html =  $doc->saveHTML();
        $resource_path_pdf = resource_path() ."/views/templates/pdf/";
        $resource_path_pdf_edit = storage_path() ."/edit-templates/pdf/";
        if(!file_exists($resource_path_pdf)){
            mkdir($resource_path_pdf,0777,true);
        }
        if(!file_exists($resource_path_pdf_edit)){
            mkdir($resource_path_pdf_edit,0777,true);
        }
        if(isset($input['id'])){
            if(file_exists($resource_path_pdf.$input['pdf_file'] . '.blade.php')){
                unlink($resource_path_pdf.$input['pdf_file'] . '.blade.php');
            }
            if(file_exists($resource_path_pdf_edit.$input['pdf_file'] . '.html')){
                unlink($resource_path_pdf_edit.$input['pdf_file'] . '.html');
            }
        }
        file_put_contents($resource_path_pdf.$folder_name . '.blade.php', $html);
        chmod($resource_path_pdf.$folder_name . '.blade.php', 0777);
        file_put_contents($resource_path_pdf_edit.$folder_name . '.html', $html_editor);
        chmod($resource_path_pdf_edit.$folder_name . '.html', 0777);
        if( isset($input['pdf_file'])){
            $update_folder_name =  $input['pdf_file'];
            $single_template = DB::table('templates')
                ->select('templates.*')
//                ->where('templates.visitor', $request->ip())
                ->where('templates.id', $input['id'])
                ->whereRaw('templates.pdf_file != "" ')
                ->orderBy('templates.title', 'asc')
                ->get()->first();
        }
        
        if(!isset($single_template)){
            $html_id = DB::table('templates')->insertGetId(['title' => $title,
                'html_file' => '', 'pdf_file' => $folder_name,
                'visitor' => $request->ip(), 'created_at' => date('Y-m-d H:i:s')]);
            $single_template = DB::table('templates')
                ->select('templates.*')
//                ->where('templates.visitor', $request->ip())
                ->where('templates.id', $html_id)
                ->whereRaw('templates.pdf_file != "" ')
                ->orderBy('templates.title', 'asc')
                ->get()->first();
        }else{
            DB::table('templates')
            ->where('id', $single_template->id)
            ->update(['title' => "$title",'pdf_file' => "$folder_name",'updated_at' => date('Y-m-d H:i:s')]);
        }
        $resource_path_pdf_edit = storage_path() ."/edit-templates/pdf/";
        $edit_html = file_get_contents($resource_path_pdf_edit.$single_template->pdf_file . '.html');
        return view('pdf.edit',['template' => $single_template,'edit_html' => $edit_html]);
//        $options = new Options();
//        $options->set('defaultFont', 'Courier');
//        $options->set('isRemoteEnabled', TRUE);
//        $options->set('isHtml5ParserEnabled', TRUE);
//        //$options->set('chroot', '');
//        $dompdf = new Dompdf($options);
//        
//        $dompdf->loadHtml($html);
//
//
//        // (Optional) Setup the paper size and orientation
//        $dompdf->setPaper('A4','landscape');
//
//        // Render the HTML as PDF
//        $dompdf->render();
//
//        // Output the generated PDF to Browser
//        $dompdf->stream(uniqid().".pdf");

    }
    
    public function templatesPdf() {
        $templates = DB::table('templates')
            ->select('templates.*')
//            ->where('templates.visitor', Req::ip())
            ->whereRaw('templates.pdf_file != "" ')
            ->orderBy('templates.title', 'asc')
            ->get();
        return view('templates-pdf',['template' => $templates]);
    }
    
    public function edit($template_title) {
        $templates = DB::table('templates')
            ->select('templates.*')
            ->where('templates.pdf_file', $template_title)
//            ->where('templates.visitor', Req::ip())
            ->whereRaw('templates.pdf_file != "" ')
            ->orderBy('templates.title', 'asc')
            ->get()->first();
        $resource_path_pdf_edit = storage_path() ."/edit-templates/pdf/";
        $edit_html = file_get_contents($resource_path_pdf_edit.$template_title . '.html');
        return view('pdf.edit',['template' => $templates,'edit_html' => $edit_html]);
    }
    public function download($template_title) {
        PDF::setOptions(['dpi' => 500, 'defaultFont' => 'sans-serif']);
        $pdf = PDF::loadView('templates.pdf.'.$template_title);
        return $pdf->download(uniqid().'.pdf');
    }
    public function preview($template_title) {
        return view('templates.pdf.'.$template_title);
    }
    public function delete($template_id){
        $single_template = DB::table('templates')
            ->select('templates.*')
//            ->where('templates.visitor', Req::ip())
            ->where('templates.id', $template_id)
            ->whereRaw('templates.pdf_file != "" ')
            ->orderBy('templates.title', 'asc')
            ->get()->first();
        $resource_path_pdf = resource_path() ."/views/templates/pdf/";
        $resource_path_pdf_edit = storage_path() ."/edit-templates/pdf/";
        if(file_exists($resource_path_pdf.$single_template->pdf_file . '.blade.php')){
            unlink($resource_path_pdf.$single_template->pdf_file . '.blade.php');
        }
        if(file_exists($resource_path_pdf_edit.$single_template->pdf_file . '.html')){
            unlink($resource_path_pdf_edit.$single_template->pdf_file . '.html');
        }
        DB::table('templates')->where('id', $template_id)->delete();
        return redirect()->action('PDFController@templatesPdf');
    }
}
