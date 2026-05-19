<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\BookmarkedArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ArticleController extends Controller
{
    // ==================== GET ALL ARTICLES ====================
    public function index(Request $request)
    {
        $query = Article::with(['author', 'category'])
            ->where('status', 'published');

        // Filter by category
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        // Search by title or author name
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhereHas('author', function($q) use ($request) {
                      $q->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        $articles = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $articles,
        ]);
    }

    // ==================== GET SINGLE ARTICLE ====================
    public function show($id)
    {
        $article = Article::with(['author', 'category'])->find($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $article,
        ]);
    }

    // ==================== CREATE ARTICLE (EXPERT ONLY) ====================
    public function store(Request $request)
    {
        // Cek apakah user adalah expert
        if ($request->user()->role !== 'expert') {
            return response()->json([
                'success' => false,
                'message' => 'Only experts can create articles',
            ], 403);
        }

        $request->validate([
            'category_id' => 'required|exists:article_categories,id',
            'title'       => 'required|string|max:200',
            'content'     => 'required|string',
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'status'      => 'nullable|in:draft,published',
        ]);

        $coverImagePath = null;
        if ($request->hasFile('cover_image')) {
            $coverImagePath = $request->file('cover_image')
                ->store('articles/covers', 'public');
        }

        $article = Article::create([
            'user_id'     => $request->user()->id,
            'category_id' => $request->category_id,
            'title'       => $request->title,
            'content'     => $request->content,
            'cover_image' => $coverImagePath,
            'status'      => $request->status ?? 'published',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Article created successfully',
            'data'    => $article->load(['author', 'category']),
        ], 201);
    }

    // ==================== UPDATE ARTICLE (EXPERT ONLY) ====================
    public function update(Request $request, $id)
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article not found',
            ], 404);
        }

        // Cek apakah artikel milik user ini
        if ($article->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this article',
            ], 403);
        }

        $request->validate([
            'category_id' => 'nullable|exists:article_categories,id',
            'title'       => 'nullable|string|max:200',
            'content'     => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'status'      => 'nullable|in:draft,published',
        ]);

        // Upload gambar baru kalau ada
        if ($request->hasFile('cover_image')) {
            // Hapus gambar lama
            if ($article->cover_image) {
                Storage::disk('public')->delete($article->cover_image);
            }
            $article->cover_image = $request->file('cover_image')
                ->store('articles/covers', 'public');
        }

        $article->update([
            'category_id' => $request->category_id ?? $article->category_id,
            'title'       => $request->title ?? $article->title,
            'content'     => $request->content ?? $article->content,
            'cover_image' => $article->cover_image,
            'status'      => $request->status ?? $article->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Article updated successfully',
            'data'    => $article->load(['author', 'category']),
        ]);
    }

    // ==================== DELETE ARTICLE (EXPERT ONLY) ====================
    public function destroy(Request $request, $id)
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article not found',
            ], 404);
        }

        // Cek apakah artikel milik user ini
        if ($article->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this article',
            ], 403);
        }

        // Hapus gambar cover kalau ada
        if ($article->cover_image) {
            Storage::disk('public')->delete($article->cover_image);
        }

        $article->delete();

        return response()->json([
            'success' => true,
            'message' => 'Article deleted successfully',
        ]);
    }

    // ==================== GET MY ARTICLES (EXPERT ONLY) ====================
    public function myArticles(Request $request)
    {
        if ($request->user()->role !== 'expert') {
            return response()->json([
                'success' => false,
                'message' => 'Only experts can access this',
            ], 403);
        }

        $articles = Article::with(['category'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $articles,
        ]);
    }

    // ==================== GET ALL CATEGORIES ====================
    public function categories()
    {
        $categories = ArticleCategory::all();

        return response()->json([
            'success' => true,
            'data'    => $categories,
        ]);
    }

    // ==================== BOOKMARK ARTICLE ====================
    public function bookmark(Request $request, $id)
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article not found',
            ], 404);
        }

        // Cek apakah sudah di-bookmark
        $existing = BookmarkedArticle::where('user_id', $request->user()->id)
            ->where('article_id', $id)
            ->first();

        if ($existing) {
            // Kalau sudah ada → unbookmark
            $existing->delete();
            return response()->json([
                'success' => true,
                'message' => 'Article unbookmarked',
                'bookmarked' => false,
            ]);
        }

        // Kalau belum ada → bookmark
        BookmarkedArticle::create([
            'user_id'    => $request->user()->id,
            'article_id' => $id,
        ]);

        return response()->json([
            'success'    => true,
            'message'    => 'Article bookmarked',
            'bookmarked' => true,
        ]);
    }

    // ==================== GET BOOKMARKED ARTICLES ====================
    public function bookmarkedArticles(Request $request)
    {
        $bookmarks = BookmarkedArticle::with(['article.author', 'article.category'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $bookmarks,
        ]);
    }
}