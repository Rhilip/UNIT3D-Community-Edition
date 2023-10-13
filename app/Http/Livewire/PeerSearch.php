<?php
/**
 * NOTICE OF LICENSE.
 *
 * UNIT3D Community Edition is open-sourced software licensed under the GNU Affero General Public License v3.0
 * The details is bundled with this project in the file LICENSE.txt.
 *
 * @project    UNIT3D Community Edition
 *
 * @author     Roardom <roardom@protonmail.com>
 * @license    https://www.gnu.org/licenses/agpl-3.0.en.html/ GNU Affero General Public License v3.0
 */

namespace App\Http\Livewire;

use App\Models\Peer;
use App\Models\Torrent;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PeerSearch extends Component
{
    use WithPagination;

    public bool $duplicateIpsOnly = false;
    public bool $includeSeedsize = false;
    public int $perPage = 25;
    public string $ip = '';
    public string $port = '';
    public string $agent = '';
    public string $torrent = '';
    public string $connectivity = 'any';
    public string $active = 'any';
    public string $groupBy = 'none';
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    protected $queryString = [
        'page'             => ['except' => 1],
        'duplicateIpsOnly' => ['except' => false],
        'includeSeedsize'  => ['except' => false],
        'perPage'          => ['except' => 25],
        'ip'               => ['except' => ''],
        'port'             => ['except' => ''],
        'agent'            => ['except' => ''],
        'torrent'          => ['except' => ''],
        'connectivity'     => ['except' => 'any'],
        'active'           => ['except' => 'any'],
        'groupBy'          => ['except' => 'none'],
        'sortField'        => ['except' => 'created_at'],
        'sortDirection'    => ['except' => 'desc'],
    ];

    final public function updatedPage(): void
    {
        $this->emit('paginationChanged');
    }

    final public function updatingIp(): void
    {
        $this->resetPage();
    }

    final public function updatingPort(): void
    {
        $this->resetPage();
    }

    final public function updatingAgent(): void
    {
        $this->resetPage();
    }

    final public function updatingTorrent(): void
    {
        $this->resetPage();
    }

    final public function updatingGroupBy(): void
    {
        if ($this->groupBy === 'none' && $this->sortField === 'size') {
            $this->sortField = 'created_at';
        }
    }

    final public function getPeersProperty(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Peer::query()
            ->when(
                $this->groupBy === 'none',
                fn ($query) => $query
                    ->select([
                        'peers.torrent_id',
                        'peers.user_id',
                        'peers.uploaded',
                        'peers.downloaded',
                        'peers.left',
                        'peers.port',
                        'peers.agent',
                        'peers.created_at',
                        'peers.updated_at',
                        'peers.seeder',
                        'peers.active',
                        'peers.connectable',
                    ])
                    ->selectRaw('INET6_NTOA(peers.ip) as ip')
                    ->with(['user', 'user.group', 'torrent:id,name,size'])
            )
            ->when(
                $this->groupBy === 'user_session',
                fn ($query) => $query
                    ->select(['peers.user_id', 'peers.port', 'peers.agent'])
                    ->selectRaw('COUNT(DISTINCT(peers.torrent_id)) as torrent_id')
                    ->selectRaw('INET6_NTOA(peers.ip) as ip')
                    ->selectRaw('SUM(peers.uploaded) as uploaded')
                    ->selectRaw('SUM(peers.downloaded) as downloaded')
                    ->selectRaw('SUM(peers.`left`) as `left`')
                    ->selectRaw('MIN(peers.created_at) as created_at')
                    ->selectRaw('MAX(peers.updated_at) as updated_at')
                    ->selectRaw('COUNT(DISTINCT(peers.id)) as peer_count')
                    ->selectRaw('SUM(peers.connectable = 1) as connectable_count')
                    ->selectRaw('SUM(peers.connectable = 0) as unconnectable_count')
                    ->selectRaw('SUM(peers.active = 1) as active_count')
                    ->selectRaw('SUM(peers.active = 0) as inactive_count')
                    ->groupBy(['peers.user_id', 'peers.agent', 'peers.ip', 'peers.port'])
                    ->with(['user', 'user.group'])
            )
            ->when(
                $this->groupBy === 'user_ip',
                fn ($query) => $query
                    ->select(['peers.user_id'])
                    ->selectRaw('COUNT(DISTINCT(peers.torrent_id)) as torrent_id')
                    ->selectRaw('COUNT(DISTINCT(peers.agent)) as agent')
                    ->selectRaw('INET6_NTOA(peers.ip) as ip')
                    ->selectRaw('COUNT(DISTINCT(peers.port)) as port')
                    ->selectRaw('SUM(peers.uploaded) as uploaded')
                    ->selectRaw('SUM(peers.downloaded) as downloaded')
                    ->selectRaw('SUM(`left`) as `left`')
                    ->selectRaw('MIN(peers.created_at) as created_at')
                    ->selectRaw('MAX(peers.updated_at) as updated_at')
                    ->selectRaw('COUNT(*) as peer_count')
                    ->selectRaw('SUM(peers.connectable = 1) as connectable_count')
                    ->selectRaw('SUM(peers.connectable = 0) as unconnectable_count')
                    ->selectRaw('SUM(peers.active = 1) as active_count')
                    ->selectRaw('SUM(peers.active = 0) as inactive_count')
                    ->groupBy(['peers.user_id', 'peers.ip'])
                    ->with(['user', 'user.group'])
            )
            ->when(
                $this->groupBy === 'user',
                fn ($query) => $query
                    ->select(['peers.user_id'])
                    ->selectRaw('COUNT(DISTINCT(peers.torrent_id)) as torrent_id')
                    ->selectRaw('COUNT(DISTINCT(peers.agent)) as agent')
                    ->selectRaw('COUNT(DISTINCT(peers.ip)) as ip')
                    ->selectRaw('COUNT(DISTINCT(peers.port)) as port')
                    ->selectRaw('SUM(peers.uploaded) as uploaded')
                    ->selectRaw('SUM(peers.downloaded) as downloaded')
                    ->selectRaw('SUM(`left`) as `left`')
                    ->selectRaw('MIN(peers.created_at) as created_at')
                    ->selectRaw('MAX(peers.updated_at) as updated_at')
                    ->selectRaw('COUNT(*) as peer_count')
                    ->selectRaw('SUM(peers.connectable = 1) as connectable_count')
                    ->selectRaw('SUM(peers.connectable = 0) as unconnectable_count')
                    ->selectRaw('SUM(peers.active = 1) as active_count')
                    ->selectRaw('SUM(peers.active = 0) as inactive_count')
                    ->groupBy(['peers.user_id'])
                    ->with(['user', 'user.group'])
            )
            ->when(
                $this->duplicateIpsOnly,
                fn ($query) => $query
                    ->whereIn(
                        'peers.ip',
                        Peer::query()
                            ->select('ip')
                            ->fromSub(Peer::select('ip', 'user_id')->distinct(), 'distinct_ips')
                            ->groupBy('ip')
                            ->havingRaw('COUNT(*) > 1')
                    )
            )
            ->when(
                $this->includeSeedsize,
                fn ($query) => $query
                    ->join('torrents', 'peers.torrent_id', '=', 'torrents.id')
                    ->when(
                        $this->groupBy === 'none',
                        fn ($query) => $query
                            ->selectRaw('torrents.size as size')
                            ->selectRaw('IF(peers.connectable = 1, torrents.size, 0) as connectable_size')
                            ->selectRaw('IF(peers.connectable = 0, torrents.size, 0) as unconnectable_size'),
                        fn ($query) => $query
                            ->selectRaw('SUM(torrents.size) as size')
                            ->selectRaw('SUM(IF(peers.connectable = 1, torrents.size, 0)) as connectable_size')
                            ->selectRaw('SUM(IF(peers.connectable = 0, torrents.size, 0)) as unconnectable_size')
                    )
            )
            ->when($this->ip !== '', fn ($query) => $query->where(DB::raw('INET6_NTOA(ip)'), 'LIKE', $this->ip.'%'))
            ->when($this->port !== '', fn ($query) => $query->where('peers.port', 'LIKE', $this->port))
            ->when($this->agent !== '', fn ($query) => $query->where('peers.agent', 'LIKE', $this->agent.'%'))
            ->when($this->torrent !== '', fn ($query) => $query->whereIn(
                'peers.torrent_id',
                Torrent::select('id')->where('name', 'LIKE', '%'.str_replace(' ', '%', $this->torrent).'%')
            ))
            ->when($this->connectivity === 'connectable', fn ($query) => $query->where('connectable', '=', true))
            ->when($this->connectivity === 'unconnectable', fn ($query) => $query->where('connectable', '=', false))
            ->when($this->active === 'include', fn ($query) => $query->where('active', '=', true))
            ->when($this->active === 'exclude', fn ($query) => $query->where('active', '=', false))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    final public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'desc';
        }

        $this->sortField = $field;
    }

    final public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Foundation\Application
    {
        return view('livewire.peer-search', [
            'peers' => $this->peers,
        ]);
    }
}
