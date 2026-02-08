
                    <ul class="nav nav-sidebar-menu sidebar-toggle-view">
                        <li class="nav-item">
                            <a href="{{ route('cultivationIndex') }}" class="nav-link"><i class="flaticon-dashboard"></i><span>Cultivation Panel</span></a>
                        </li>
                        <li class="nav-item sidebar-nav-item">
                            <a href="#" class="nav-link"><i class="fa-regular fa-building-flag"></i><span>student Fees</span></a>
                            <ul class="nav sub-group-menu">
                                <li class="nav-item">
                                    <a href="{{ route('tuitionFee') }}" class="nav-link"><i
                                            class="fas fa-angle-right"></i>Collect Fees</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('tuitionFeeList') }}" class="nav-link"><i
                                            class="fas fa-angle-right"></i>Fees List</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('feesReport') }}" class="nav-link"><i
                                            class="fas fa-angle-right"></i>Genetrate Report</a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item sidebar-nav-item">
                            <a href="#" class="nav-link"><i class="fa-regular fa-book-open"></i><span>Cash Calculas</span></a>
                            <ul class="nav sub-group-menu">
                                <li class="nav-item">
                                    <a href="{{ route('cashCalculasView') }}" class="nav-link"><i
                                            class="fas fa-angle-right"></i>Debit/Credit</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('reportListView') }}" class="nav-link"><i class="fas fa-angle-right"></i> Get Report</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('cashDateReport') }}" class="nav-link"><i class="fas fa-angle-right"></i> Get Date Report</a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item ">
                            <a href="{{route('feesForm')}}" class="nav-link"><i class="fa-thin fa-database"></i> <span>Add Fees Name</span></a>
                        </li>
                        <li class="nav-item sidebar-nav-item {{ request()->routeIs('userGuide.cashAdmin') ? 'open' : '' }}" data-group="cash-guides">
                            <a href="#" class="nav-link {{ request()->routeIs('userGuide.cashAdmin') ? 'active' : '' }}"><i class="fa-regular fa-book"></i><span>User Guides</span></a>
                            <ul class="nav sub-group-menu{{ request()->routeIs('userGuide.cashAdmin') ? ' menu-open' : '' }}">
                                <li class="nav-item">
                                    <a href="{{ route('userGuide.cashAdmin') }}" class="nav-link {{ request()->routeIs('userGuide.cashAdmin') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Cash Admin</a>
                                </li>
                            </ul>
                        </li>
                    </ul>