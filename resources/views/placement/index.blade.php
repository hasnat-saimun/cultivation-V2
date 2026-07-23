@extends('result.include')

@section('content')
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-semibold mb-4">GPA-Based Placements</h1>

    @if(session('success'))
        <div class="bg-green-100 text-green-800 p-2 rounded mb-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 text-red-800 p-2 rounded mb-3">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('placements.recalculate') }}" class="grid grid-cols-1 md:grid-cols-6 gap-3 mb-6">
        @csrf
        <input type="text" name="sessionId" value="{{ old('sessionId', $filters['sessionId'] ?? '') }}" placeholder="Session ID" class="border rounded p-2" />
        <input type="text" name="classId" value="{{ old('classId', $filters['classId'] ?? '') }}" placeholder="Class ID" class="border rounded p-2" />
        <input type="text" name="groupId" value="{{ old('groupId', $filters['groupId'] ?? '') }}" placeholder="Group ID (optional)" class="border rounded p-2" />
        <input type="text" name="examId" value="{{ old('examId', $filters['examId'] ?? '') }}" placeholder="Exam ID" class="border rounded p-2" />
        <input type="text" name="departmentId" value="{{ old('departmentId', $filters['departmentId'] ?? '') }}" placeholder="Department ID (optional)" class="border rounded p-2" />
        @if(config('result_engine.placement_enabled'))
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="force" value="1"> Confirm overwrite if published</label>
        @endif
        <button type="submit" class="bg-blue-600 text-white rounded p-2">Recalculate</button>
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border">
            <thead>
                <tr class="bg-gray-100">
                    <th class="px-3 py-2 border">Pos</th>
                    <th class="px-3 py-2 border">Student</th>
                    <th class="px-3 py-2 border">Roll</th>
                    <th class="px-3 py-2 border">Subjects</th>
                    <th class="px-3 py-2 border">GPA</th>
                    <th class="px-3 py-2 border">Total Marks</th>
                    <th class="px-3 py-2 border">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($placements as $p)
                    @php($admission = \App\Models\newAdmission::find($p->studentId))
                    <tr>
                        <td class="px-3 py-2 border text-center">{{ $p->position }}</td>
                        <td class="px-3 py-2 border">{{ $admission->fullName ?? $p->studentId }}</td>
                        <td class="px-3 py-2 border">{{ $admission->rollNumber ?? '—' }}</td>
                        <td class="px-3 py-2 border text-center">{{ $p->subjectsCount }}</td>
                        <td class="px-3 py-2 border text-center font-semibold">{{ number_format($p->gpa, 2) }}</td>
                        <td class="px-3 py-2 border text-center">{{ $p->totalMarks }}</td>
                        <td class="px-3 py-2 border">{{ $p->status }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-4 text-center">No placements found. Use the form above to calculate.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $placements->links() }}</div>
</div>
@endsection
