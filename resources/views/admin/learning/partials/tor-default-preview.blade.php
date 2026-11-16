<div @if($torLive ?? false) data-official-tor @endif class="absolute inset-0 overflow-hidden bg-white" @if(($torLive ?? false) && (($savedBackground ?? '') !== '' || ($backgroundIsPdf ?? false))) hidden @endif style="container-type: size;">
    <div class="bg-white text-slate-950" style="width: 740px; height: 1046px; transform: scale(calc(100cqw / 740px)); transform-origin: top left; font-family: 'Times New Roman', Times, serif;">
        <div style="box-sizing: border-box; display: flex; height: 100%; flex-direction: column; padding: 28px 34px 16px;">
            <div style="display: flex; align-items: center; justify-content: center; gap: 14px;">
                <img src="{{ asset('assets/official-logo.png') }}" alt="" style="height: 72px; width: 72px; object-fit: contain;">
                <div style="text-align: center;">
                    <p style="margin: 0; font-size: 22px; line-height: 1.1;">{{ $organization['name'] ?? 'Mission Care Training Center' }}</p>
                    <p style="margin: 4px 0 0; font-size: 14px; line-height: 1.25;">{{ $organization['address'] ?? '' }}<br>{{ $organization['phone'] ?? '' }}</p>
                    <p style="margin: 2px 0 0; font-size: 24px; font-weight: 700; line-height: 1;">REGISTRAR</p>
                </div>
            </div>
            <div style="height: 5px; margin: 12px 40px 14px; background: #9c7ac3;"></div>
            <p data-tor-title style="margin: 0; text-align: center; font-size: 22px; font-weight: 700;">{{ $torTitle }}</p>
            <p style="margin: 4px 0 0; text-align: center; font-size: 28px; font-weight: 700; line-height: 1.1;">"{{ $torTrainee }}"</p>
            <p data-tor-program style="margin: 2px 0 0; text-align: center; font-size: 20px; font-weight: 700;">{{ $torProgram ?? 'CAREGIVING NC II' }}</p>
            <p style="margin: 0; text-align: center; font-size: 16px; font-style: italic; line-height: 1.15;">Course<br>({{ $torHours ?? ($organization['course_hours'] ?? 786) }} Hours)</p>
            <div style="display: grid; min-height: 0; flex: 1; grid-template-columns: 236px 1fr; margin-top: 14px; border: 1.5px solid #111;">
                <div style="background: #c9c9f8; padding: 8px 10px; font-size: 13px; line-height: 1.25;">
                    <p style="margin: 0 0 8px;"><span style="display: block; font-weight: 700;">Entrance Date:</span>September 5, 2026</p>
                    <p style="margin: 0 0 8px;"><span style="display: block; font-weight: 700;">Address:</span>San Isidro Poblacion, Pili, Camarines Sur</p>
                    <p style="margin: 0 0 8px;"><span style="display: block; font-weight: 700;">Last School Attended:</span>MCARE School<span style="display: block; margin-top: 4px; font-weight: 700;">Category:</span>Regular</p>
                    <p style="margin: 0 0 8px;"><span style="display: block; font-weight: 700;">TITLE/COURSE:</span><span data-tor-course>{{ $torProgram ?? 'CAREGIVING NC II' }}</span><span style="display: block; margin-top: 4px; font-weight: 700; font-style: italic;">Date Completed/Graduated:</span>September 30, 2026</p>
                    <p style="margin: 0 0 8px;"><span style="display: block; font-weight: 700;">Official Marks:</span>1.00 - 99% &nbsp; 2.00 - 87%<br>1.25 - 96% &nbsp; 2.25 - 84%<br>1.50 - 93% &nbsp; 2.50 - 81%<br>1.75 - 90% &nbsp; 2.75 - 78%<br>3.00 - 75%<br>1.00-3.00 Competent<br>4.00-5.00 Not Yet Competent</p>
                    <p style="margin: 0;"><span style="display: block; font-weight: 700;">Remarks:</span><span data-tor-remarks style="font-style: italic;">{{ $torRemarks ?? 'For Records and Reference Purposes' }}</span></p>
                </div>
                <div style="display: flex; flex-direction: column; background: #facbfa;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                        <thead>
                            <tr>
                                <th style="width: 18%; border: 1px solid #111; padding: 4px 2px;">Course Code</th>
                                <th style="border: 1px solid #111; padding: 4px 2px;">Course Title</th>
                                <th style="width: 16%; border: 1px solid #111; padding: 4px 2px;">Final Grade</th>
                                <th style="width: 20%; border: 1px solid #111; padding: 4px 2px;">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ([
                                ['HCS323301', 'Provide Care and Support to Infants and Toddlers', '3.00'],
                                ['HCS323302', 'Provide Care and Support to Children', '3.00'],
                                ['HCS323303', 'Foster Social, Intellectual, Creative and Emotional Development of Children', '1.75'],
                                ['HCS323304', 'Foster Physical Development of Children', '3.00'],
                                ['HCS323305', 'Provide Care and Support to Elderly', '3.00'],
                                ['HCS323306', 'Provide Care and Support to People with Special Needs', '3.00'],
                                ['HCS323307', 'Maintain a Healthy and Safe Environment', '3.00'],
                                ['HCS323308', 'Respond to Emergency', '3.00'],
                                ['HCS323309', 'Clean Living Room, Dining Room, Bedrooms, Toilet and Bathroom', '3.00'],
                                ['HCS323310', 'Wash and Iron Clothes, Linen and Fabric', '3.00'],
                                ['HCS323311', 'Prepare Hot and Cold Meals', '3.00'],
                            ] as $row)
                                <tr>
                                    <td style="border: 1px solid #111; padding: 3px 2px; text-align: center;">{{ $row[0] }}</td>
                                    <td style="border: 1px solid #111; padding: 3px 4px;">{{ $row[1] }}</td>
                                    <td style="border: 1px solid #111; padding: 3px 2px; text-align: center; font-weight: 700;">{{ $row[2] }}</td>
                                    <td style="border: 1px solid #111; padding: 3px 2px; text-align: center; font-weight: 700;">COMPETENT</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p style="margin: 0; flex: 1; padding-top: 10px; text-align: center; font-size: 13px; letter-spacing: 0.04em;">* * * * * NOTHING FOLLOWS * * * * *</p>
                </div>
            </div>
            <div style="display: grid; height: 96px; grid-template-columns: 236px 1fr 1fr; border: 1.5px solid #111; border-top: 0; text-align: center; font-size: 13px;">
                <div style="display: flex; flex-direction: column; justify-content: space-between; background: #c9c9f8; padding: 6px 8px;">
                    <span>Prepared by:</span>
                    <span><span data-tor-trainer style="display: block; font-weight: 700; text-transform: uppercase;">{{ $torTrainer }}</span><span data-tor-trainer-role>{{ $torTrainerRole }}</span></span>
                </div>
                <div style="display: flex; flex-direction: column; justify-content: space-between; border-left: 1.5px solid #111; background: #facbfa; padding: 6px 8px;">
                    <span>Certified by:</span>
                    <span><span data-tor-registrar style="display: block; font-weight: 700; text-transform: uppercase;">{{ $torRegistrar }}</span><span data-tor-registrar-role>{{ $torRegistrarRole }}</span></span>
                </div>
                <div style="display: flex; flex-direction: column; justify-content: space-between; border-left: 1.5px solid #111; background: #facbfa; padding: 6px 8px;">
                    <span>Seal</span>
                    <span>Not valid without official seal</span>
                </div>
            </div>
            <p style="margin: 4px 0 0; text-align: right; font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #64748b;">Document no. MCARE-TOR-2026-00002-V1 | Version 1</p>
        </div>
    </div>
</div>
