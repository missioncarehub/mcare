<div @if($torLive ?? false) data-official-tor @endif class="absolute inset-0 overflow-hidden bg-white tor-preview-canvas" @if(($torLive ?? false) && (($savedBackground ?? '') !== '' || ($backgroundIsPdf ?? false))) hidden @endif style="container-type: size;">
    <style>
        .tor-field-guide-badge {
            display: none;
            position: absolute;
            z-index: 40;
            background: #7c3aed;
            color: #ffffff;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
            padding: 4px 8px;
            border-radius: 9999px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
            border: 1.5px solid #ffffff;
            cursor: pointer;
            pointer-events: auto;
            white-space: nowrap;
            transition: all 0.15s ease;
        }
        .tor-field-guide-badge:hover {
            background: #5b21b6;
            transform: scale(1.1);
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.35);
        }
        .show-field-guides .tor-field-guide-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .tor-highlight-active {
            outline: 3px solid #7c3aed !important;
            background-color: #f3e8ff !important;
            transition: all 0.3s ease;
        }
    </style>

    @php
        $resolvedRows = $torRows ?? [
            ['HCS323301', 'Provide Care and Support to Infants and Toddlers', '1.25', 'COMPETENT'],
            ['HCS323302', 'Provide Care and Support to Children', '1.25', 'COMPETENT'],
            ['HCS323303', 'Foster Social, Intellectual, Creative and Emotional Development of Children', '1.50', 'COMPETENT'],
            ['HCS323304', 'Foster Physical Development of Children', '1.25', 'COMPETENT'],
            ['HCS323305', 'Provide Care and Support to Elderly', '1.25', 'COMPETENT'],
            ['HCS323306', 'Provide Care and Support to People with Special Needs', '1.25', 'COMPETENT'],
            ['HCS323307', 'Maintain a Healthy and Safe Environment', '1.25', 'COMPETENT'],
            ['HCS323308', 'Respond to Emergency', '1.25', 'COMPETENT'],
            ['HCS323309', 'Clean Living Room, Dining Room, Bedrooms, Toilet and Bathroom', '1.50', 'COMPETENT'],
            ['HCS323310', 'Wash and Iron Clothes, Linen and Fabric', '1.25', 'COMPETENT'],
            ['HCS323311', 'Prepare Hot and Cold Meals', '1.25', 'COMPETENT'],
        ];
    @endphp

    <div class="bg-white text-slate-950" style="width: 740px; height: 1046px; transform: scale(calc(100cqw / 740px)); transform-origin: top left; font-family: 'Times New Roman', Times, serif;">
        <div style="box-sizing: border-box; display: flex; height: 100%; flex-direction: column; padding: 24px 32px 14px;">
            
            {{-- Header with Official Logo --}}
            <div style="display: flex; align-items: center; justify-content: center; gap: 14px; position: relative;">
                <img src="{{ asset('assets/official-logo.png') }}" alt="MCARE Logo" style="height: 68px; width: 68px; object-fit: contain;">
                <div style="text-align: center;">
                    <p style="margin: 0; font-size: 21px; font-weight: 700; line-height: 1.1; color: #0f172a;">{{ $organization['name'] ?? 'Mission Care Training Center' }}</p>
                    <p style="margin: 4px 0 0; font-size: 13px; line-height: 1.25; color: #334155;">{{ $organization['address'] ?? 'San Isidro Poblacion, Pili, Camarines Sur' }}<br>{{ $organization['phone'] ?? '+63 912 345 6789' }}</p>
                    <p style="margin: 2px 0 0; font-size: 22px; font-weight: 700; line-height: 1; letter-spacing: 0.05em; color: #475569;">REGISTRAR</p>
                </div>
            </div>

            {{-- Lavender Separator Bar --}}
            <div style="height: 5px; margin: 10px 30px 12px; background: #9c7ac3;"></div>

            {{-- Document Heading --}}
            <div style="position: relative; text-align: center;">
                <span class="tor-field-guide-badge" data-guide-target="tor-title-input" style="top: -12px; left: 50%; transform: translateX(-50%);">① Document Title</span>
                <p data-tor-title style="margin: 0; text-align: center; font-size: 21px; font-weight: 700; letter-spacing: 0.02em;">{{ $torTitle }}</p>
            </div>

            {{-- Trainee Name --}}
            <div style="position: relative; text-align: center; margin-top: 3px;">
                <span class="tor-field-guide-badge" style="top: -10px; right: 20px; background: #0284c7;">② Trainee Full Name</span>
                <p style="margin: 0; text-align: center; font-size: 26px; font-weight: 700; line-height: 1.1; color: #09090b;">"{{ $torTrainee }}"</p>
            </div>

            {{-- Program Title & Course Duration --}}
            <div style="position: relative; text-align: center; margin-top: 2px;">
                <span class="tor-field-guide-badge" style="top: -10px; left: 20px; background: #4f46e5;">③ Program Title &amp; Hours</span>
                <p data-tor-program style="margin: 0; text-align: center; font-size: 19px; font-weight: 700; text-transform: uppercase;">{{ $torProgram ?? 'CAREGIVING NC II' }}</p>
                <p style="margin: 0; text-align: center; font-size: 15px; font-style: italic; line-height: 1.15; color: #334155;">Course<br>({{ $torHours ?? ($organization['course_hours'] ?? 786) }} Hours)</p>
            </div>

            {{-- Main Two-Column Content Grid --}}
            <div style="display: grid; min-height: 0; flex: 1; grid-template-columns: 236px 1fr; margin-top: 12px; border: 1.5px solid #111;">
                
                {{-- Left Column: Student Details & Remarks --}}
                <div style="background: #c9c9f8; padding: 8px 10px; font-size: 13px; line-height: 1.25; position: relative;">
                    <span class="tor-field-guide-badge" style="top: 4px; right: 4px; background: #3b82f6;">④ Academic Details</span>

                    <p style="margin: 0 0 8px;"><span style="display: block; font-weight: 700;">Entrance Date:</span>{{ $torEntranceDate ?? 'January 15, 2026' }}</p>
                    <p style="margin: 0 0 8px;"><span style="display: block; font-weight: 700;">Address:</span>{{ $torAddress ?? 'San Isidro Poblacion, Pili, Camarines Sur' }}</p>
                    <p style="margin: 0 0 8px;"><span style="display: block; font-weight: 700;">Last School Attended:</span>{{ $torLastSchool ?? 'MCARE Assessment Center' }}<span style="display: block; margin-top: 4px; font-weight: 700;">Category:</span>{{ $torCategory ?? 'Regular Trainee' }}</p>
                    <p style="margin: 0 0 8px;"><span style="display: block; font-weight: 700;">TITLE/COURSE:</span><span data-tor-course>{{ $torProgram ?? 'CAREGIVING NC II' }}</span><span style="display: block; margin-top: 4px; font-weight: 700; font-style: italic;">Date Completed/Graduated:</span>{{ $torGraduationDate ?? 'July 18, 2026' }}</p>
                    <p style="margin: 0 0 8px;"><span style="display: block; font-weight: 700;">Official Marks:</span>1.00 - 99% &nbsp; 2.00 - 87%<br>1.25 - 96% &nbsp; 2.25 - 84%<br>1.50 - 93% &nbsp; 2.50 - 81%<br>1.75 - 90% &nbsp; 2.75 - 78%<br>3.00 - 75%<br>1.00-3.00 Competent<br>4.00-5.00 Not Yet Competent</p>
                    
                    {{-- Remarks block --}}
                    <div style="position: relative; margin-top: 6px;">
                        <span class="tor-field-guide-badge" data-guide-target="tor-body-input" style="top: -12px; left: 0; background: #d97706;">⑤ Official Remarks</span>
                        <p style="margin: 0;"><span style="display: block; font-weight: 700;">Remarks:</span><span data-tor-remarks style="font-style: italic;">{{ $torRemarks ?? 'For Records and Reference Purposes' }}</span></p>
                    </div>
                </div>

                {{-- Right Column: Competencies & Grades Matrix --}}
                <div style="display: flex; flex-direction: column; background: #facbfa; position: relative;">
                    <span class="tor-field-guide-badge" style="top: 4px; right: 4px; background: #059669;">⑥ Competency Units &amp; Grades</span>

                    <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                        <thead>
                            <tr style="background: rgba(255, 255, 255, 0.4);">
                                <th style="width: 20%; border: 1px solid #111; padding: 4px 2px; font-weight: 700;">Course Code</th>
                                <th style="border: 1px solid #111; padding: 4px 4px; font-weight: 700;">Course Title / Unit of Competency</th>
                                <th style="width: 17%; border: 1px solid #111; padding: 4px 2px; font-weight: 700; text-align: center;">Final Grade</th>
                                <th style="width: 20%; border: 1px solid #111; padding: 4px 2px; font-weight: 700; text-align: center;">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($resolvedRows as $row)
                                <tr>
                                    <td style="border: 1px solid #111; padding: 3px 3px; text-align: center; font-family: monospace; font-size: 11px;">{{ $row[0] }}</td>
                                    <td style="border: 1px solid #111; padding: 3px 5px; font-size: 11.5px;">{{ $row[1] }}</td>
                                    <td style="border: 1px solid #111; padding: 3px 2px; text-align: center; font-weight: 700;">{{ $row[2] }}</td>
                                    <td style="border: 1px solid #111; padding: 3px 2px; text-align: center; font-weight: 700; color: #15803d;">{{ $row[3] ?? 'COMPETENT' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p style="margin: 0; flex: 1; padding-top: 8px; text-align: center; font-size: 12.5px; letter-spacing: 0.05em; font-weight: 700; color: #475569;">* * * * * NOTHING FOLLOWS * * * * *</p>
                </div>
            </div>

            {{-- Approval / Signature Blocks --}}
            <div style="display: grid; height: 96px; grid-template-columns: 236px 1fr 1fr; border: 1.5px solid #111; border-top: 0; text-align: center; font-size: 13px;">
                {{-- Prepared by (Trainer) --}}
                <div style="display: flex; flex-direction: column; justify-content: space-between; background: #c9c9f8; padding: 6px 8px; position: relative;">
                    <span class="tor-field-guide-badge" data-guide-target="tor-trainer-name" style="top: -12px; left: 50%; transform: translateX(-50%); background: #0284c7;">⑦ Prepared by (Trainer)</span>
                    <span style="font-weight: 600;">Prepared by:</span>
                    <span>
                        <span data-tor-trainer style="display: block; font-weight: 700; text-transform: uppercase;">{{ $torTrainer }}</span>
                        <span data-tor-trainer-role style="font-size: 11.5px; color: #334155;">{{ $torTrainerRole }}</span>
                    </span>
                </div>

                {{-- Certified by (Registrar) --}}
                <div style="display: flex; flex-direction: column; justify-content: space-between; border-left: 1.5px solid #111; background: #facbfa; padding: 6px 8px; position: relative;">
                    <span class="tor-field-guide-badge" data-guide-target="tor-registrar-name" style="top: -12px; left: 50%; transform: translateX(-50%); background: #7c3aed;">⑧ Certified by (Registrar)</span>
                    <span style="font-weight: 600;">Certified by:</span>
                    <span>
                        <span data-tor-registrar style="display: block; font-weight: 700; text-transform: uppercase;">{{ $torRegistrar }}</span>
                        <span data-tor-registrar-role style="font-size: 11.5px; color: #334155;">{{ $torRegistrarRole }}</span>
                    </span>
                </div>

                {{-- Seal Block --}}
                <div style="display: flex; flex-direction: column; justify-content: space-between; border-left: 1.5px solid #111; background: #facbfa; padding: 6px 8px; position: relative;">
                    <span class="tor-field-guide-badge" style="top: -12px; left: 50%; transform: translateX(-50%); background: #64748b;">⑨ Official Seal</span>
                    <span style="font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Seal</span>
                    <span style="font-size: 11px; font-style: italic; color: #475569;">Not valid without official seal</span>
                </div>
            </div>

            {{-- Footer Verification Line --}}
            <p style="margin: 4px 0 0; text-align: right; font-family: Helvetica, Arial, sans-serif; font-size: 10.5px; color: #64748b;">
                Document no. MCARE-TOR-2026-00001-V1 | Version 1
            </p>
        </div>
    </div>
</div>
