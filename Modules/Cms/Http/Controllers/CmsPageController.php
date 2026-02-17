<?php

namespace Modules\Cms\Http\Controllers;

use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Cms\Entities\CmsPage;
use Modules\Cms\Entities\CmsPageMeta;
use Carbon\Carbon;

class CmsPageController extends Controller
{
    protected $commonUtil;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(Util $commonUtil)
    {
        $this->commonUtil = $commonUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $post_type = $request->get('type', 'page');
        $pages = CmsPage::where('type', $post_type)
        ->orderBy('created_at', 'DESC')
        ->paginate(24);
        if($post_type == 'gallery'){
            return view('cms::page.gallery',compact('pages','post_type'));  
        }else{
            return view('cms::page.index')->with(compact('pages', 'post_type'));      
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create(Request $request)
    {
        $post_type = $request->get('type', 'page');
        
        
        $pages = CmsPage::where('type', $post_type)->orderBy('priority', 'asc')->get();
        
        $related_notes = collect([]);
        
        foreach($pages as $page){
            $link= null;
            $aux = null;
            $title = str_replace('?','', $page->title);
            $tile_note = strtolower(str_replace(' ', '-', $title));
            $link= $tile_note.'-'.$page->id;
            $aux =  (object) [
            'title' => $page->title,
            'description' =>'Referencia de otra nota',
            'content' => '<div style=\"background-color: #f1f1f1;padding: 1rem;border-top: 0.2px solid #ccc;border-bottom: 0.2px solid #ccc;\"> <div style=\"align-items: center;display: flex;\"> <div style=\"white-space: nowrap;font-size: .75rem;\">LEER TAMBIÉN</div></div> <div style=\"align-items: center;display: flex;\">  <h4 style=\"flex-basis: 75%;\"><a title=\"Nota libra\" href=\"https://librainternational.com.pe/blog-detail/'. $link .'\">'. $page->title. '</a></h4> <div style=\"flex-basis: 25%;\">  <img src=\"https://admin.librainternational.com.pe/uploads/cms/'. $page->feature_image .'\" alt=\"Noticia de libra International\" style=\"max-width: 100%; height: auto;\"> </div></div></div></a> <br>'
            ];
            $related_notes->push($aux);
        }


        return view('cms::page.create')
            ->with(compact('post_type','related_notes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        //check if app is in demo & disable action
        $notAllowedInDemo = $this->commonUtil->notAllowedInDemo();
        if (! empty($notAllowedInDemo)) {
            return $notAllowedInDemo;
        }

        try {
            $input = $request->only(['title', 'content', 'meta_description',
                'tags', 'priority', 'type','slug'
            ]);

            $input['created_by'] = $request->session()->get('user.id');

            $input['feature_image'] = $this->commonUtil->uploadFile($request, 'feature_image', 'cms', 'image');

            $input['is_enabled'] = ! empty($request->is_enabled);

            $page = CmsPage::create($input);

            $output = ['success' => 1,
                'msg' => __('lang_v1.added_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => __('lang_v1.something_went_wrong'),
            ];
        }

        return redirect()
            ->action([\Modules\Cms\Http\Controllers\CmsPageController::class, 'index'], ['type' => $input['type'] ?? 'page'])
            ->with('status', $output);
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function showPage($page_title)
    {
        $title = str_replace('-', ' ', $page_title);

        $page = CmsPage::where('title', $title)
                    ->first();

        if (empty($page)) {
            abort(404);
        }

        return view('cms::frontend.pages.custom_view')
        ->with(compact('page'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $post_type = request()->get('type', 'page');
        
        
        $pages = CmsPage::where('type', $post_type)->orderBy('priority', 'asc')->get();
        
        $related_notes = collect([]);
        
        foreach($pages as $page){
            $link= null;
            $aux = null;
            $title = str_replace('?','', $page->title);
            $tile_note = strtolower(str_replace(' ', '-', $title));
            $link= $tile_note.'-'.$page->id;
            $aux =  (object) [
            'title' => $page->title,
            'description' =>'Referencia de otra nota',
            'content' => '<div style=\"background-color: #f1f1f1;padding: 1rem;border-top: 0.2px solid #ccc;border-bottom: 0.2px solid #ccc;\"> <div style=\"align-items: center;display: flex;\"> <div style=\"white-space: nowrap;font-size: .75rem;\">LEER TAMBIÉN</div></div> <div style=\"align-items: center;display: flex;\">  <h4 style=\"flex-basis: 75%;\"><a title=\"Nota libra\" href=\"https://librainternational.com.pe/blog-detail/'. $link .'\">'. $page->title. '</a></h4> <div style=\"flex-basis: 25%;\">  <img src=\"https://admin.librainternational.com.pe/uploads/cms/'. $page->feature_image .'\" alt=\"Noticia de libra International\" style=\"max-width: 100%; height: auto;\"> </div></div></div></a> <br>'
            ];
            $related_notes->push($aux);
        }
        
        
        
        

        $page = CmsPage::where('type', $post_type)
                    ->findOrFail($id);

        $page_meta = CmsPageMeta::where('cms_page_id', $id)
                        ->get()
                        ->keyBy('meta_key');

        return view('cms::page.edit')
            ->with(compact('page', 'post_type', 'page_meta','related_notes'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //check if app is in demo & disable action
        $notAllowedInDemo = $this->commonUtil->notAllowedInDemo();
        if (! empty($notAllowedInDemo)) {
            return $notAllowedInDemo;
        }

        try {
            $input = $request->only(['title', 'content', 'meta_description',
                'tags', 'priority', 'type',
            ]);

            $page = CmsPage::findOrFail($id);

            if ($request->hasFile('feature_image')) {
                $input['feature_image'] = $this->commonUtil->uploadFile($request, 'feature_image', 'cms', 'image');
                //delete previous feature image from storage
                if (! empty($page->feature_image_path) && file_exists($page->feature_image_path)) {
                    unlink($page->feature_image_path);
                }
            }

            if($request->date){
                 $cur_date =Carbon::createFromFormat('Y-m-d', $request->date)->format('Y-m-d H:i:s');
                 $input['created_at'] = $cur_date;
            }
            $input['is_enabled'] = ! empty($request->is_enabled);

            $page->update($input);

            if (! empty($request->input('meta'))) {
                CmsPageMeta::updateOrCreateMetaForPage($request->input('meta'), $id);
            }

            $output = ['success' => 1,
                'msg' => __('lang_v1.updated_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => __('lang_v1.something_went_wrong'),
            ];
        }

        return redirect()
            ->back()
            ->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //check if app is in demo & disable action
        $notAllowedInDemo = $this->commonUtil->notAllowedInDemo();
        if (! empty($notAllowedInDemo)) {
            return $notAllowedInDemo;
        }

        if (request()->ajax()) {
            try {
                $post_type = request()->get('type');

                $page = CmsPage::where('type', $post_type)
                        ->findOrFail($id);

                if (! empty($page->feature_image_path) && file_exists($page->feature_image_path)) {
                    unlink($page->feature_image_path);
                }

                $page->delete();

                $output = ['success' => true,
                    'msg' => __('unit.deleted_success'),
                ];
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                $output = ['success' => false,
                    'msg' => '__("messages.something_went_wrong")',
                ];
            }

            return $output;
        }
    }
}
