<table class="meta-wrap mt-4">
    <tr>
        <td class="meta-left">
            <table class="student-info">
                <tbody>
                    <tr><th>Student ID</th><td>:</td><td colspan="4">{{ $identity['studentId'] ?: '-' }}</td></tr>
                    <tr><th>Name</th><td>:</td><td colspan="4">{{ $identity['studentName'] }}</td></tr>
                    <tr><th>Father Name</th><td>:</td><td colspan="4">{{ $identity['fatherName'] }}</td></tr>
                    <tr><th>Mother Name</th><td>:</td><td colspan="4">{{ $identity['motherName'] }}</td></tr>
                    <tr>
                        <th>Roll Number</th><td>:</td><td>{{ $identity['rollNumber'] }}</td>
                        <th>Session</th><td>:</td><td>{{ $metadata['sessionName'] }}</td>
                    </tr>
                    <tr>
                        <th>Class</th><td>:</td><td>{{ $metadata['className'] }}</td>
                        <th>Section</th><td>:</td><td>{{ $metadata['sectionName'] }}</td>
                    </tr>
                    <tr><th>Department</th><td>:</td><td colspan="4">{{ $metadata['departmentName'] }}</td></tr>
                    <tr><th>Merit Position</th><td>:</td><td colspan="4">{{ $meritRank ?? '-' }}</td></tr>
                </tbody>
            </table>
        </td>
        <td class="meta-right">
            @include('result.partials.grading-table', ['gradeLegend' => $gradeLegend])
        </td>
    </tr>
</table>
