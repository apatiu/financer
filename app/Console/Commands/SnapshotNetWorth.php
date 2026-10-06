<?php

namespace App\Console\Commands;

use App\Models\Workspace;
use App\Services\NetWorthService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('net-worth:snapshot {--workspace= : บันทึกเฉพาะ workspace id นี้}')]
#[Description('บันทึกความมั่งคั่งสุทธิของทุก workspace ณ วันนี้ สำหรับกราฟแนวโน้ม')]
class SnapshotNetWorth extends Command
{
    public function handle(NetWorthService $netWorth): int
    {
        $workspaces = Workspace::query()
            ->when($this->option('workspace'), fn ($query, $id) => $query->whereKey($id))
            ->get();

        foreach ($workspaces as $workspace) {
            $netWorth->snapshot($workspace);
        }

        $this->info("บันทึกแล้ว {$workspaces->count()} workspace");

        return self::SUCCESS;
    }
}
