<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\College;
use Livewire\WithPagination;

class CollegeList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public $search = ''; // সার্চের জন্য ভেরিয়েবল
    public $isModalOpen = false;
    public $selectedCollege = null;

    // কেউ সার্চ করলে যেন পেজিনেশন প্রথম পেজে ফিরে আসে
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function viewDetails($collegeId)
    {
        $this->selectedCollege = College::with([
            'userDetail',
            'collegeSubjects.course',
            'collegeSubjects.subject'
        ])->find($collegeId);

        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->selectedCollege = null;
    }

    public function render()
    {
        // সার্চ ইনপুটের ওপর ভিত্তি করে ডাটা ফিল্টার করা হচ্ছে
        $colleges = College::where('college_code', 'like', '%' . $this->search . '%')
            ->orWhere('college_name', 'like', '%' . $this->search . '%')
            ->orWhere('email', 'like', '%' . $this->search . '%')
            ->paginate(10);

        return view('livewire.college-list', compact('colleges'));
    }
}
