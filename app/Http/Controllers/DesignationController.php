<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Designation;

class DesignationController extends Controller
{
    public function index()
    {
        $designations = Designation::orderBy('type')->orderBy('sort_order')->paginate(50);
        return view('cultivation.designations.index', compact('designations'));
    }

    public function create()
    {
        $types = ['teacher' => 'Teacher', 'staff' => 'Staff', 'committee' => 'Governing Body'];
        return view('cultivation.designations.create', compact('types'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|trim',
            'type' => 'required|in:teacher,staff,committee',
        ]);

        // Normalize name and check for duplicates (case-insensitive)
        $name = trim($request->input('name'));
        $type = $request->input('type');
        
        $existing = Designation::whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->where('type', $type)
            ->first();

        if ($existing) {
            return back()->with('error', 'Designation "' . $name . '" already exists for this type')->withInput();
        }

        $maxOrder = Designation::where('type', $type)->max('sort_order') ?? 0;

        Designation::create([
            'name' => $name,
            'type' => $type,
            'sort_order' => $maxOrder + 1,
            'is_active' => true,
        ]);

        return back()->with('success', 'Designation "' . $name . '" added successfully');
    }

    public function edit($id)
    {
        $designation = Designation::findOrFail($id);
        $types = ['teacher' => 'Teacher', 'staff' => 'Staff', 'committee' => 'Governing Body'];
        return view('cultivation.designations.edit', compact('designation', 'types'));
    }

    public function update(Request $request, $id)
    {
        $designation = Designation::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:teacher,staff,committee',
        ]);

        $existing = Designation::where('name', $request->name)
            ->where('type', $request->type)
            ->where('id', '!=', $id)
            ->first();

        if ($existing) {
            return back()->with('error', 'A designation with this name already exists for this type');
        }

        $updateData = [
            'name' => $request->input('name'),
            'type' => $request->input('type'),
            'is_active' => $request->has('is_active') ? 1 : 0,
        ];

        $designation->update($updateData);

        return back()->with('success', 'Designation updated successfully');
    }

    public function delete($id)
    {
        $designation = Designation::findOrFail($id);
        $designation->delete();

        return back()->with('success', 'Designation deleted successfully');
    }

    public function reorder(Request $request)
    {
        try {
            $order = $request->input('order', []);

            foreach ($order as $position => $id) {
                Designation::where('id', $id)->update(['sort_order' => $position + 1]);
            }

            return response()->json(['success' => true, 'message' => 'Order updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function toggleActive($id)
    {
        $designation = Designation::findOrFail($id);
        $designation->update(['is_active' => !$designation->is_active]);

        return back()->with('success', 'Status updated successfully');
    }
}
