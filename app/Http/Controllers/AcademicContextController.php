<?php

namespace App\Http\Controllers;

use App\Domain\AcademicContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AcademicContextController extends Controller
{
    public function update(Request $request, AcademicContext $context): RedirectResponse
    {
        $data = $request->validate(['academic_year_id' => 'required|integer']);
        $year = $context->availableYears($request->user())->findOrFail($data['academic_year_id']);
        $context->remember($request, $year);

        return redirect()->route('dashboard');
    }
}
