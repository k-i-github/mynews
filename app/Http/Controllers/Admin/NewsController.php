<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

// 以下を追記することでNews Modelが扱えるようになる laravel_14
use App\News;

// 以下追記 Laravel_17
use App\History;
use Carbon\carbon;
use Storage; // 追加

class NewsController extends Controller
{
    //　以下を追記
    public function add()
    {
        return view('admin.news.create');
    }
    

    //　以下を追記
    public function create(Request $request)
    {
        
        // 以下を追記 laravel_14
        // varidationを行う
        $this->validate($request, News::$rules);
        
        $news = new News;
        $form = $request->all();
        
        // フォームから画像が送信されてきたら、保存して、 $news->image_path に画像のパスを保存する
        if (isset($form['image'])) {
            $path = Storage::disk('s3')->putFile('/',$form['image'],'public');
            $news->image_path = Storage::disk('s3')->url($path);
        } else {
            $news->image_path = null;
        }
        
        
        // フォームから送信されてきた_tokenを削除する
        unset($form['_token']);
        // フォームから送信されてきたimageを削除する
        unset($form['image']);
        
        // データベースに保存する
        $news->fill($form);
        $news->save();
        
        // admin/news/createにリダイレクトする
        return redirect('admin/news/create');
    }
    
    // 以下を追記する　Laravel_15
    public function index(Request $request)
    {
        $cond_title = $request->cond_title;
        if ($cond_title != '') {
            // 検索されたら検索結果を取得する
            $posts = News::where('title', $cond_title)->get();
        } else {
            // それ以外はすべてのニュースを取得する
            $posts = News::all();
        }
        return view('admin.news.index', ['posts' => $posts, 'cond_title' => $cond_title]);
    }
    
    // 以下を追記aravel_16
    
    public function edit(Request $request)
    {
        // News Modelからデータを取得する
        $news = News::find($request->id);
        if (empty($news)) {
            abort(404);
        }
        return view('admin.news.edit', ['news_form' => $news]);
    }
    
    public function update(Request $request)
    {
        // Validationをかける
        $this->validate($request, News::$rules);
        // News Modelからデータを取得する
        $news = News::find($request->id);
        // 送信されてきたフォームデータを格納する
        $news_form = $request->all();
        // 画像データ変更
        if (isset($news_form['image'])) {
            $path = Storage::disk('s3')->putFile('/',$form['image'],'public');
            $news->image_path = Storage::disk('s3')->url($path);
            unset($news_form['image']);
        } elseif (isset($request->remove)) {
            $news->image_path = null;
            unset($news_form['remove']);
        }
        unset($news_form['_token']);
        
        // 該当するデータを上書きして保存する
        $news->fill($news_form)->save();
        
        // 以下を追記 Laravel17
        $historty = new History;
        $historty->news_id = $news->id;
        $historty->edited_at = Carbon::now();
        $historty->save();
        
        return redirect('admin/news/');
    }
    
    // 以下追加 _16
    public function delete(Request $request)
    {
        // 該当するNews Modelを取得
        $news = News::find($request->id);
        // 削除する
        $news->delete();
        return redirect('admin/news/');
    }
}