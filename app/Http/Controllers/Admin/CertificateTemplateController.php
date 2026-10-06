<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use App\Services\Certificate\CertificateDataResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateTemplateController extends Controller
{
    public function __construct(protected CertificateDataResolver $resolver)
    {
        $this->middleware('auth');
        $this->middleware('permission:Manage certificate templates');
    }

    public function index()
    {
        return view('certificates.templates.index', [
            'pagetitle' => 'Certificate Templates',
            'templates' => CertificateTemplate::withCount('certificates')->when(request('kind'), fn ($q) => $q->where('kind', request('kind')))->orderByDesc('id')->paginate(20)->withQueryString(),
            'kind'      => request('kind'),
        ]);
    }

    public function create()
    {
        return view('certificates.templates.designer', [
            'pagetitle' => 'New Certificate Template',
            'template'  => new CertificateTemplate(['orientation' => request('kind') === 'testimonial' ? 'portrait' : 'landscape', 'width' => request('kind') === 'testimonial' ? 794 : 1123, 'height' => request('kind') === 'testimonial' ? 1123 : 794, 'requires_approval' => true, 'serial_prefix' => request('kind') === 'testimonial' ? 'TST' : 'CERT', 'kind' => request('kind') === 'testimonial' ? 'testimonial' : 'certificate']),
            'catalog'   => $this->resolver->catalog(),
        ]);
    }

    public function edit(CertificateTemplate $template)
    {
        return view('certificates.templates.designer', [
            'pagetitle' => 'Edit Certificate Template',
            'template'  => $template,
            'catalog'   => $this->resolver->catalog(),
        ]);
    }

    public function store(Request $request)
    {
        $template = CertificateTemplate::create($this->validated($request) + ['created_by' => $request->user()->id]);
        return response()->json(['success' => true, 'id' => $template->id, 'message' => 'Template saved.', 'redirect' => route('certificates.templates.edit', $template)]);
    }

    public function update(Request $request, CertificateTemplate $template)
    {
        $template->update($this->validated($request));
        return response()->json(['success' => true, 'id' => $template->id, 'message' => 'Template updated.']);
    }

    public function destroy(CertificateTemplate $template)
    {
        if ($template->certificates()->exists()) {
            return back()->with('error', 'This template has issued certificates and cannot be deleted. Deactivate it instead.');
        }
        $template->delete();
        return back()->with('success', 'Template deleted.');
    }

    public function duplicate(CertificateTemplate $template)
    {
        $copy = $template->replicate(['created_by']);
        $copy->name = $template->name . ' (copy)';
        $copy->created_by = request()->user()->id;
        $copy->save();
        return redirect()->route('certificates.templates.edit', $copy)->with('success', 'Template duplicated.');
    }

    /** Image upload from the designer (background / logos / decorations). Same-origin so the canvas can export. */
    public function uploadAsset(Request $request)
    {
        $request->validate(['file' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:6144']);
        $path = $request->file('file')->store('certificates/assets', 'public');
        return response()->json(['success' => true, 'url' => asset('storage/' . $path), 'path' => $path]);
    }

    protected function validated(Request $request): array
    {
        $d = $request->validate([
            'name'              => 'required|string|max:150',
            'kind'              => 'nullable|in:certificate,testimonial',
            'description'       => 'nullable|string|max:500',
            'orientation'       => 'required|in:landscape,portrait',
            'width'             => 'required|integer|min:200|max:5000',
            'height'            => 'required|integer|min:200|max:5000',
            'serial_prefix'     => 'nullable|string|max:30',
            'requires_approval' => 'nullable|boolean',
            'generation_limit'  => 'nullable|integer|min:0|max:1000',
            'design'            => 'nullable|string',
            'background_path'   => 'nullable|string|max:255',
            'is_active'         => 'nullable|boolean',
        ]);

        return [
            'name'              => $d['name'],
            'kind'              => $d['kind'] ?? 'certificate',
            'description'       => $d['description'] ?? null,
            'orientation'       => $d['orientation'],
            'width'             => (int) $d['width'],
            'height'            => (int) $d['height'],
            'serial_prefix'     => strtoupper(trim($d['serial_prefix'] ?? 'CERT')) ?: 'CERT',
            'requires_approval' => $request->boolean('requires_approval'),
            'generation_limit'  => ($d['generation_limit'] ?? null) ? (int) $d['generation_limit'] : null,
            'design'            => $d['design'] ?? null,
            'background_path'   => $d['background_path'] ?? null,
            'is_active'         => $request->boolean('is_active', true),
        ];
    }
}
